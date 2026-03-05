<?php

namespace App\Service;

use App\Entity\Formation;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing Google Calendar events for Formations.
 *
 * Uses a Service Account for server-to-server authentication.
 * The target calendar must be shared with the Service Account with
 * "Make changes to events" permission.
 *
 * This implementation uses Google\Client + direct Calendar API HTTP calls
 * so it does not require the google/apiclient-services package (which fails
 * to install on Windows due to path length limits).
 *
 * Required environment variables:
 *   GOOGLE_APPLICATION_CREDENTIALS: Path to the service account JSON key file.
 *   GOOGLE_CALENDAR_ID:             The calendar owner's email (e.g. your@gmail.com)
 *                                    that you shared with the Service Account.
 */
class GoogleCalendarService
{
    private const CALENDAR_SCOPE = 'https://www.googleapis.com/auth/calendar';
    private const CALENDAR_API_BASE = 'https://www.googleapis.com/calendar/v3';
    private const DEFAULT_TIMEZONE = 'Europe/Paris';

    private ?\Google\Client $client = null;
    private string $calendarId;
    private string $resolvedCredentialsPath;

    public function __construct(
        private readonly string $credentialsPath,
        private readonly string $calendarIdParam,
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
    ) {
        $this->calendarId = trim($calendarIdParam);
        $this->resolvedCredentialsPath = $this->resolveCredentialsPath($credentialsPath);
    }

    private function resolveCredentialsPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        $path = str_replace('%kernel.project_dir%', $this->projectDir, $path);
        if ($path !== '' && $path[0] !== '/' && !preg_match('#^[A-Za-z]:[/\\\\]#', $path)) {
            $path = rtrim($this->projectDir, '/\\') . \DIRECTORY_SEPARATOR . $path;
        }
        return $path;
    }

    /**
     * Resolves path to a CA bundle for SSL verification (fixes cURL error 60 on Windows).
     * Tries: GOOGLE_SSL_CA_FILE, php.ini curl.cainfo/openssl.cafile, then var/cacert.pem or config/cacert.pem in project.
     */
    private function resolveCaBundlePath(): ?string
    {
        $env = getenv('GOOGLE_SSL_CA_FILE');
        if ($env !== false && $env !== '') {
            $path = str_replace('%kernel.project_dir%', $this->projectDir, trim($env));
            if ($path !== '' && $path[0] !== '/' && !preg_match('#^[A-Za-z]:[/\\\\]#', $path)) {
                $path = rtrim($this->projectDir, '/\\') . \DIRECTORY_SEPARATOR . $path;
            }
            if (is_file($path)) {
                return $path;
            }
        }
        foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile')] as $iniPath) {
            if ($iniPath !== false && $iniPath !== '' && is_file($iniPath)) {
                return $iniPath;
            }
        }
        foreach (['var/cacert.pem', 'config/cacert.pem'] as $relative) {
            $path = rtrim($this->projectDir, '/\\') . \DIRECTORY_SEPARATOR . $relative;
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function getClient(): \Google\Client
    {
        if ($this->client === null) {
            if ($this->resolvedCredentialsPath === '' || !is_file($this->resolvedCredentialsPath)) {
                throw new \RuntimeException(sprintf(
                    'Google Calendar: credentials file not found at "%s" (resolved from "%s"). '
                    . 'Use the SERVICE ACCOUNT JSON key (Google Cloud Console → IAM → Service Accounts → Keys → Add key → JSON). '
                    . 'Do not use the OAuth "client secret" file.',
                    $this->resolvedCredentialsPath,
                    $this->credentialsPath
                ));
            }

            $this->client = new \Google\Client();
            $this->client->setApplicationName('SmartTask Manager');
            $this->client->setAuthConfig($this->resolvedCredentialsPath);
            $this->client->setScopes([self::CALENDAR_SCOPE]);

            // On Windows, cURL often fails with "SSL certificate problem: unable to get local issuer certificate".
            // Use a CA bundle: set GOOGLE_SSL_CA_FILE in .env to the path of cacert.pem (e.g. from https://curl.se/ca/cacert.pem).
            $caFile = $this->resolveCaBundlePath();
            if ($caFile !== null) {
                $this->client->setHttpClient(new \GuzzleHttp\Client(['verify' => $caFile]));
            }

            $this->logger->debug('GoogleCalendarService: Client initialized.', [
                'credentials_path' => $this->resolvedCredentialsPath,
                'calendar_id'      => $this->calendarId,
            ]);
        }

        return $this->client;
    }

    /**
     * Sends an authenticated request to the Calendar API and returns the decoded JSON body.
     * For 204 No Content (e.g. DELETE) returns []. Throws on API error (4xx/5xx).
     */
    private function sendRequest(string $method, string $uri, ?string $body = null): array
    {
        $url = str_starts_with($uri, 'http') ? $uri : self::CALENDAR_API_BASE . $uri;
        $headers = ['Content-Type' => 'application/json'];
        $request = new Request($method, $url, $headers, $body);

        $response = $this->getClient()->execute($request, false);
        if (!$response instanceof ResponseInterface) {
            return [];
        }

        $responseBody = (string) $response->getBody();
        if ($responseBody === '') {
            return [];
        }

        $decoded = json_decode($responseBody, true);
        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from Calendar API: ' . json_last_error_msg());
        }

        return $decoded;
    }

    private function validateCalendarId(): void
    {
        $id = $this->calendarId;
        if (strtolower($id) === 'primary') {
            $this->logger->warning('GoogleCalendarService: GOOGLE_CALENDAR_ID is "primary". With a Service Account use the calendar owner email (e.g. your@gmail.com) that you shared with the Service Account.');
            return;
        }
        if (str_ends_with(strtolower($id), '.iam.gserviceaccount.com')) {
            $this->logger->warning('GoogleCalendarService: GOOGLE_CALENDAR_ID appears to be the Service Account email. Set it to the calendar owner email (e.g. your@gmail.com) that you shared with the Service Account.');
        }
    }

    /**
     * Creates a Google Calendar event for the given Formation.
     *
     * @return string|null The created event ID, or null on failure.
     */
    public function createEvent(Formation $formation): ?string
    {
        $formationId = $formation->getId();
        $context = ['formation_id' => $formationId, 'calendar_id' => $this->calendarId];

        try {
            $this->validateCalendarId();

            $startDate = $formation->getDateDebut();
            $endDate   = $formation->getDateFin();

            if (!$startDate || !$endDate) {
                $this->logger->warning('GoogleCalendarService: Formation dates are missing, skipping event creation.', $context);
                return null;
            }

            $startStr = $startDate->format('Y-m-d');
            $endObj = $endDate instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($endDate) : new \DateTimeImmutable($endDate->format('Y-m-d'));
            $endStr = $endObj->modify('+1 day')->format('Y-m-d'); // exclusive for all-day

            $eventPayload = [
                'summary'     => $formation->getTitre() ?? 'Formation',
                'description' => $formation->getDescription() ?? '',
                'start'       => ['date' => $startStr, 'timeZone' => self::DEFAULT_TIMEZONE],
                'end'         => ['date' => $endStr, 'timeZone' => self::DEFAULT_TIMEZONE],
            ];

            $this->logger->debug('GoogleCalendarService: Inserting event.', [
                'summary' => $eventPayload['summary'],
                'start'   => $startStr,
                'end'     => $endStr,
                'calendar_id' => $this->calendarId,
            ]);

            $calendarIdEnc = rawurlencode($this->calendarId);
            $uri = "/calendars/{$calendarIdEnc}/events?sendUpdates=none";
            $created = $this->sendRequest('POST', $uri, json_encode($eventPayload));

            $eventId = $created['id'] ?? null;
            $htmlLink = $created['htmlLink'] ?? null;

            $this->logger->info('GoogleCalendarService: Event created successfully.', [
                'event_id'     => $eventId,
                'formation_id' => $formationId,
                'calendar_id'  => $this->calendarId,
                'html_link'    => $htmlLink,
            ]);

            return $eventId;
        } catch (\Exception $e) {
            $this->logger->error('GoogleCalendarService: Failed to create event.', [
                'error'        => $e->getMessage(),
                'formation_id' => $formationId,
                'calendar_id'  => $this->calendarId,
                'exception'    => $e::class,
                'trace'        => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Adds an attendee (by email) to an existing Google Calendar event.
     */
    public function addAttendeeToEvent(string $eventId, string $userEmail): void
    {
        try {
            $calendarIdEnc = rawurlencode($this->calendarId);
            $eventIdEnc = rawurlencode($eventId);
            $event = $this->sendRequest('GET', "/calendars/{$calendarIdEnc}/events/{$eventIdEnc}");

            $attendees = $event['attendees'] ?? [];
            foreach ($attendees as $a) {
                if (strcasecmp($a['email'] ?? '', $userEmail) === 0) {
                    $this->logger->info('GoogleCalendarService: Attendee already in event.', ['email' => $userEmail, 'event_id' => $eventId]);
                    return;
                }
            }

            $attendees[] = ['email' => $userEmail];
            $this->sendRequest('PATCH', "/calendars/{$calendarIdEnc}/events/{$eventIdEnc}?sendUpdates=all", json_encode(['attendees' => $attendees]));

            $this->logger->info('GoogleCalendarService: Attendee added to event.', ['email' => $userEmail, 'event_id' => $eventId]);
        } catch (\Exception $e) {
            $this->logger->error('GoogleCalendarService: Failed to add attendee.', [
                'error' => $e->getMessage(),
                'email' => $userEmail,
                'event_id' => $eventId,
            ]);
        }
    }

    /**
     * Deletes a Google Calendar event by its ID.
     */
    public function deleteEvent(string $eventId): void
    {
        try {
            $calendarIdEnc = rawurlencode($this->calendarId);
            $eventIdEnc = rawurlencode($eventId);
            $this->sendRequest('DELETE', "/calendars/{$calendarIdEnc}/events/{$eventIdEnc}");

            $this->logger->info('GoogleCalendarService: Event deleted.', ['event_id' => $eventId, 'calendar_id' => $this->calendarId]);
        } catch (\Exception $e) {
            $this->logger->error('GoogleCalendarService: Failed to delete event.', [
                'error' => $e->getMessage(),
                'event_id' => $eventId,
            ]);
        }
    }
}
