<?php

namespace App\Domain\Session\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes a session.
 * @OA\Schema(description="session description")
 */
class SessionData
{
    /**
     * The entity ID.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id = null;

    /**
     * The session title.
     * @var string|null
     * @OA\Property()
     */
    public ?string $title;

    /**
     * The session description.
     * @var string|null
     * @OA\Property()
     */
    public ?string $description;

    /**
     * The session subject.
     * @var string|null
     * @OA\Property()
     */
    public ?string $subject;

    /**
     * The key with which the participants can connect to the session.
     * @var string|null
     * @OA\Property(example="ABCD1234")
     */
    public ?string $connectionKey;

    /**
     * What is the maximum number of users allowed to participate in the session?
     * @var int|null
     * @OA\Property(example=1000)
     */
    public ?int $maxParticipants;

    /**
     * How long is the session valid?
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $expirationDate;

    /**
     * When was the session created?
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $creationDate;

    /**
     * Public screen module ID.
     * @var string|null
     * @OA\Property(example=null)
     */
    public ?string $publicScreenModuleId;

    /**
     * Role in the session.
     * @var string|null
     * @OA\Property()
     */
    public ?string $role;

    /**
     * Number of topics assigned to the session.
     * @var int
     * @OA\Property()
     */
    public int $topicCount;

    /**
     * Number of tasks assigned to the session.
     * @var int
     * @OA\Property()
     */
    public int $taskCount;

    /**
     * Allow everyone to view public screen.
     * @var bool
     * @OA\Property()
     */
    public bool $allowAnonymous;

    /**
     * Creates a new Participant.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->title = $reader->findString("title");
        $this->description = $reader->findString("description");
        $this->subject = $reader->findString("subject");
        $this->connectionKey = $reader->findString("connection_key");
        $this->maxParticipants = $reader->findInt("max_participants");
        $this->expirationDate = $reader->findString("expiration_date");
        $this->creationDate = $reader->findString("creation_date");
        $this->publicScreenModuleId = $reader->findString("public_screen_module_id");
        $this->role = $reader->findString("role");
        $this->topicCount = $reader->findInt("topic_count") ?? 0;
        $this->taskCount = $reader->findInt("task_count") ?? 0;
        $this->allowAnonymous = $reader->findBool("allow_anonymous") ?? false;

        if (isset($this->role)) {
            $this->role = strtoupper($this->role);
        }
    }
}
