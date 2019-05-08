<?php

namespace Firevel\FirestoreSessionDriver;

use Carbon\Carbon;
use DateTime;
use DateInterval;
use Google\Cloud\Core\Timestamp;
use Google\Cloud\Firestore\FirestoreClient;
use Illuminate\Foundation\Application;
use SessionHandlerInterface;

class FirestoreSessionHandler implements SessionHandlerInterface
{
    /**
     * Firestore client.
     *
     * @var Google\Cloud\Firestore\FirestoreClient
     */
    protected $client;

    /**
     * Collection name.
     *
     * @var string
     */
    protected $collection;

    /**
     * The number of minutes the session should be valid.
     *
     * @var int
     */
    protected $minutes;

    /**
     * The container instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $container;

    /**
     * Create a new firestore session handler instance.
     *
     * @param  \Illuminate\Database\FirestoreClient  $client
     * @param  int  $minutes
     * @param  \Illuminate\Foundation\Application|null  $container
     * @return void
     */
    public function __construct(FirestoreClient $client, $collection, $minutes, Application $container = null)
    {
        $this->client = $client;
        $this->collection = $collection;
        $this->minutes = $minutes;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        $session = (object) $this->getCollection()->document($sessionId)->snapshot()->data();

        if ($this->expired($session)) {
            return '';
        }

        if (isset($session->payload)) {
            return base64_decode($session->payload);
        }

        return '';
    }

    /**
     * Determine if the session is expired.
     *
     * @param  \stdClass  $session
     * @return bool
     */
    protected function expired($session)
    {
        return isset($session->last_activity) &&
            Carbon::parse((string)$session->last_activity)->getTimestamp() < Carbon::now()->subMinutes($this->minutes)->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $payload = $this->getDefaultPayload($data);

		$this->getCollection()->document($sessionId)->set($payload);

        return $this;
    }

    /**
     * Get the default payload for the session.
     *
     * @param  string  $data
     * @return array
     */
    protected function getDefaultPayload($data)
    {
        $payload = [
            'payload' => base64_encode($data),
            'last_activity' => new Timestamp(new DateTime()),
        ];

        if (! $this->container) {
            return $payload;
        }

        return tap($payload, function (&$payload) {
            $this->addUserInformation($payload)
                 ->addRequestInformation($payload);
        });
    }

    /**
     * Add the user information to the session payload.
     *
     * @param  array  $payload
     * @return $this
     */
    protected function addUserInformation(&$payload)
    {
        if ($this->container->bound(Guard::class)) {
            $payload['user_id'] = $this->userId();
        }

        return $this;
    }

    /**
     * Get the currently authenticated user's ID.
     *
     * @return mixed
     */
    protected function userId()
    {
        return $this->container->make(Guard::class)->id();
    }

    /**
     * Add the request information to the session payload.
     *
     * @param  array  $payload
     * @return $this
     */
    protected function addRequestInformation(&$payload)
    {
        if ($this->container->bound('request')) {
            $payload = array_merge($payload, [
                'ip_address' => $this->ipAddress(),
                'user_agent' => $this->userAgent(),
            ]);
        }

        return $this;
    }

    /**
     * Get the IP address for the current request.
     *
     * @return string
     */
    protected function ipAddress()
    {
        return $this->container->make('request')->ip();
    }

    /**
     * Get the user agent for the current request.
     *
     * @return string
     */
    protected function userAgent()
    {
        return (string) $this->container->make('request')->header('User-Agent');
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->getCollection()->document($sessionId)->reference()->delete();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
    	$dateTime = new DateTime();
    	$dateTime->sub(new DateInterval("PT{$lifetime}S"));
    	$dateTime = new Timestamp($dateTime);
    	$sessions = $this
    		->getCollection()
    		->limit(env('SESSION_GC_BATCH_SIZE', 100))
    		->where('last_activity', '<=', $dateTime)
    		->documents();

    	foreach ($sessions as $session) {
    		$session->reference()->delete();
    	}
    }

    /** 
     * Get a collection reference.
     *
     * @return CollectionReference
     */
    protected function getCollection()
    {
        return $this->client->collection($this->collection);
    }
}