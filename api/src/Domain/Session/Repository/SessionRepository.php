<?php

namespace App\Domain\Session\Repository;

use App\Domain\Base\Data\ModificationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\KeyGeneratorTrait;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Module\Repository\ModuleRepository;
use App\Domain\Participant\Data\ParticipantInfoData;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Domain\Session\Data\SessionInfo;
use App\Domain\Task\Data\TaskData;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Topic\Data\ExportData;
use App\Domain\Topic\Data\TopicData;
use App\Domain\Topic\Repository\TopicRepository;
use App\Domain\Topic\Type\ExportType;
use App\Domain\User\Repository\UserRepository;
use App\Domain\Session\Type\SessionRoleType;
use App\Domain\User\Type\UserRoleType;
use App\Factory\QueryFactory;
use App\Domain\Session\Data\SessionData;
use DomainException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Selective\ArrayReader\ArrayReader;

/**
 * Repository
 */
class SessionRepository implements RepositoryInterface
{
    use KeyGeneratorTrait;
    use RepositoryTrait {
        RepositoryTrait::insert as private genericInsert;
        RepositoryTrait::update as private genericUpdate;
    }

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->setUp(
            $queryFactory,
            "session",
            SessionData::class,
            "user_id",
            UserRepository::class
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param string|null $detailEntity Detail entity which should be modified
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(?string $id, string | null $detailEntity = null): ?string
    {
        $authorisation = $this->getAuthorisation();
        if (!is_null($authorisation->id)) {
            $conditions = [
                "session_id" => $id,
                "user_id" => $authorisation->id,
                "user_type" => $authorisation->type
            ];
            $role = $this->getAuthorisationRoleFromCondition(
                $id,
                $conditions,
                "session_permission",
                false
            );

            if ($authorisation->isParticipant() and $role == strtoupper(SessionRoleType::PARTICIPANT)) {
                $result = $this->get([
                    "session.id" => $id
                ]);
                if (!is_object($result)) {
                    return strtoupper(SessionRoleType::EXPIRED);
                }
            }

            return $role;
        } else {
            $query = $this->queryFactory->newSelect("session");
            $query->select(["'anonymous' AS role"])
                ->andWhere([
                    "id" => $id,
                    "allow_anonymous" => 1,
                ]);
            return $this->getAuthorisationRoleFromQuery($id, $query, false);
        }
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return SessionData|array<SessionData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|SessionData|array
    {
        if (count($sortConditions) == 0) {
            $sortConditions = ["creation_date"];
        }

        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [
            "session_permission.user_id" => $authorisation->id,
            "session_permission.user_type" => $authorisation->type
        ];

        if ($authorisation->isParticipant()) {
            array_push($conditions, "session.expiration_date >= current_timestamp()");
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        if (is_null($authorisation->id)) {
            $authorisation_conditions = [
                "allow_anonymous" => 1,
            ];
            $query->select(["session.*", "'anonymous' AS role"]);
        } else {
            $query->select(["session.*", "session_permission.role"])
                ->innerJoin("session_permission", "session_permission.session_id = session.id");
        }
        $query->andWhere($authorisation_conditions)
            ->andWhere($conditions)
            ->order($sortConditions);

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $this->getInfos($resultItem);
            }
        } elseif (is_object($result)) {
            $this->getInfos($result);
        }
        return $result;
    }

    /**
     * get all used session subjects
     * @return array list of session subjects
     * @throws GenericException
     */
    public function getSubjects(): array
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [
            "session_permission.user_id" => $authorisation->id,
            "session_permission.user_type" => $authorisation->type
        ];

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["session.subject"])
            ->innerJoin("session_permission", "session_permission.session_id = session.id")
            ->andWhere($authorisation_conditions)
            ->distinct(["session.subject"]);

        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            $result = [];
            foreach ($rows as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $subject = $reader->findString("subject");
                array_push($result, $subject);
            }
            return $result;
        }
        return [];
    }

    /**
     * Has entity changes
     * @param array $conditions The WHERE conditions to add with AND.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function hasChangesByConditions(array $conditions = []): ModificationData
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = [
            "session_permission.user_id" => $authorisation->id,
            "session_permission.user_type" => $authorisation->type
        ];
        if (is_null($authorisation->id)) {
            $authorisation_conditions = [
                "allow_anonymous" => 1,
            ];
        } else {
            $query->innerJoin("session_permission", "session_permission.session_id = session.id");
        }
        $query->select(["modification_date"])
            ->andWhere($authorisation_conditions);
        if (sizeof($conditions) > 0) $query->andWhere($conditions);
        $query->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Has changes for the parent ID
     * @param string $parentId The entity parent ID.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByParentId(string $parentId): ModificationData
    {
        return $this->lastModificationByConditions([]);
    }

    /**
     * Get session infos
     * @param SessionData $data Session data
     */
    private function getInfos(SessionData $data): void
    {
        $query = $this->queryFactory->newSelect("topic");
        $query->select(["COUNT(DISTINCT(topic.id)) AS TOPIC_COUNT", "COUNT(task.id) AS TASK_COUNT"])
            ->leftJoin("task", "task.topic_id = topic.id")
            ->andWhere([
                "topic.session_id" => $data->id
            ]);
        $rows = $query->execute()->fetch();
        $reader = new ArrayReader($rows);
        $data->topicCount = $reader->findInt("0") ?? 0;
        $data->taskCount = $reader->findInt("1") ?? 0;
    }

    /**
     * Get session infos modification
     * @param int $sessionId Session data
     * @return ModificationData Modification Data
     */
    private function getInfosModification(int $sessionId): ModificationData
    {
        $query = $this->queryFactory->newSelect("topic");
        $query->select(["modification_date"])
            ->andWhere([
                "topic.session_id" => $sessionId
            ])
            ->order(["modification_date" => "DESC"]);
        $topicTimestamp = $this->getLastModificationTimestamp($query);
        $query = $this->queryFactory->newSelect("topic");
        $query->select(["task.modification_date"])
            ->leftJoin("task", "task.topic_id = topic.id")
            ->andWhere([
                "topic.session_id" => $sessionId
            ])
            ->order(["modification_date" => "DESC"]);
        $taskTimestamp = $this->getLastModificationTimestamp($query);
        return new ModificationData([
            "lastModification" => max($topicTimestamp->lastModification, $taskTimestamp->lastModification),
            "callTimestamp" => min($topicTimestamp->callTimestamp, $taskTimestamp->callTimestamp),
            "rowCount" => $topicTimestamp->rowCount + $taskTimestamp->rowCount
        ]);
    }

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return SessionData|null The result entity.
     * @throws GenericException
     */
    public function getById(string $id): ?SessionData
    {
        $result = $this->get([
            "session.id" => $id
        ]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $parentId The entity parent ID.
     * @return array<SessionData> The result entity list.
     * @throws GenericException
     */
    public function getAll(string $parentId): array
    {
        $result = $this->get([
            #"session_permission.user_state" => "active"
        ]);
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get list all public template sessions.
     * @return array<SessionData> The result entity list.
     * @throws GenericException
     */
    public function getTemplates(): array
    {
        $sortConditions = ["creation_date"];
        $authorisation_conditions = ["visibility" => 'template'];

        $query = $this->queryFactory->newSelect($this->getEntityName())->select(["*"])
            ->andWhere($authorisation_conditions)
            ->order($sortConditions);

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $this->getInfos($resultItem);
            }
        } elseif (is_object($result)) {
            $this->getInfos($result);
        }
        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get list of entities for the connection keys.
     * @param array $keys The entity connection keys.
     * @return array<SessionData> The result entity list.
     * @throws GenericException
     */
    public function getListByKeys(array $keys): array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["connection_key", "title"])
            ->andWhere(["expiration_date >= current_timestamp()"])
            ->whereInList("connection_key", $keys);

        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            $result = [];
            foreach ($rows as $resultItem) {
                array_push($result, new SessionInfo($resultItem));
            }
            return $result;
        }
        return [];
    }

    /**
     * Get last modification date of entities for the connection keys.
     * @param array $keys The entity connection keys.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function getListByKeysModification(array $keys): ModificationData
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["modification_date"])
            ->andWhere(["expiration_date >= current_timestamp()"])
            ->whereInList("connection_key", $keys)
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Get list of participants for the session ID.
     * @param string $sessionId The session ID.
     * @return array<ParticipantInfoData> The result entity list.
     */
    public function getParticipants(string $sessionId): array
    {
        $subQueryCountIdeas = $this->queryFactory->newSelect("idea")
            ->select(["idea.participant_id", "COUNT(idea.id) AS idea_count"])
            ->innerJoin("participant", "idea.participant_id = participant.id")
            ->andWhere(["participant.session_id" => $sessionId])
            ->group(["idea.participant_id"]);
        $subQueryCountVotes = $this->queryFactory->newSelect("vote")
            ->select(["vote.participant_id", "COUNT(vote.id) AS vote_count"])
            ->innerJoin("participant", "vote.participant_id = participant.id")
            ->andWhere(["participant.session_id" => $sessionId])
            ->group(["vote.participant_id"]);

        $query = $this->queryFactory->newSelect("participant");
        $query->select(["participant.*", "idea_count", "vote_count"])
            ->join([
                "idea" => [
                    "table" => $subQueryCountIdeas,
                    "type" => "LEFT",
                    "conditions" => [
                        "idea.participant_id = participant.id",
                    ]
                ]
            ])
            ->join([
                "vote" => [
                    "table" => $subQueryCountVotes,
                    "type" => "LEFT",
                    "conditions" => [
                        "vote.participant_id = participant.id",
                    ]
                ]
            ])
            ->andWhere([
                "participant.session_id" => $sessionId,
            ])
            ->order(["participant.modification_date" => "DESC"]);

        $result = $this->fetchAll($query, ParticipantInfoData::class);

        if (is_array($result)) {
            return $result;
        } elseif (isset($result)) {
            return [$result];
        }
        return [];
    }

    /**
     * Get last modification date of participants for the session ID.
     * @param string $sessionId The session ID.
     * @return ModificationData Modification Data
     */
    public function getParticipantsModification(string $sessionId): ModificationData
    {
        $query = $this->queryFactory->newSelect("participant");
        $query->select(["participant.modification_date"])
            ->andWhere([
                "session_id" => $sessionId,
            ])
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Insert session row.
     * @param object $data The session data
     * @param bool $insertDependencies If false, ignore insertDependencies function
     * @return object|null The new session
     */
    public function insert(object $data, bool $insertDependencies = true): ?object
    {
        if (!isset($data->connectionKey))
            $data->connectionKey = $this->generateNewConnectionKey("connection_key");
        $data->creationDate = date("Y-m-d");
        return $this->genericInsert($data, $insertDependencies);
    }

    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function update(object|array $data): ?object
    {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->role != UserRoleType::ADMIN) {
            unset($data->visibility);
        }
        return $this->genericUpdate($data);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
        if (is_object($parameter)) {
            $this->queryFactory->newInsert("session_role", [
                "session_id" => $id,
                "user_id" => $parameter->userId,
                "role" => strtoupper(SessionRoleType::OWNER)
            ])->execute();
        }
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("participant");
        $query->select(["id"]);
        $query->andWhere(["session_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $participant = new ParticipantRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $participantId = $resultItem["id"];
                $participant->deleteById($participantId);
            }
        }

        $query = $this->queryFactory->newSelect("topic");
        $query->select(["id"]);
        $query->andWhere(["session_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $topic = new TopicRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $topicId = $resultItem["id"];
                $topic->deleteById($topicId);
            }
        }

        $this->queryFactory->newDelete("resource")
            ->andWhere(["session_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("session_role")
            ->andWhere(["session_id" => $id])
            ->execute();
    }

    /**
     * Export all session data as spread sheet
     * @param string $id Id of the session to be exported
     * @param string $exportType Export output format
     * @return ExportData | null string converted export data
     * @throws Exception|\PhpOffice\PhpSpreadsheet\Exception
     */
    public function export(string $id, string $exportType): ExportData | null
    {
        $path = "export";
        if (!is_dir($path)) {
            mkdir($path);
        }
        $path = $path . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($path)) {
            mkdir($path);
        } else {
            $files = glob($path . DIRECTORY_SEPARATOR . "*", GLOB_MARK);
            foreach ($files as $file) {
                if (!is_dir($file)) {
                    unlink($file);
                }
            }
        }
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        //get topics
        $query = $this->queryFactory->newSelect("topic");
        $query->select(["*"])
            ->andWhere(["session_id" => $id])
            ->order("order");
        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            $topic = new TopicRepository($this->queryFactory);
            foreach ($rows as $index => $topicItem) {
                $reader = new ArrayReader($topicItem);
                $detailId = $reader->findString("id");
                $topic->fillSpreadsheet($spreadsheet, $detailId, $path);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $url = null;
        switch (strtolower($exportType)) {
            case ExportType::XLSX:
                $url = $path . DIRECTORY_SEPARATOR . "topic-export-$id.xlsx";
                $writer = new Xlsx($spreadsheet);
                $writer->save($url);
                break;
            case ExportType::XLS:
                $url = $path . DIRECTORY_SEPARATOR . "topic-export-$id.xls";
                $writer = new Xls($spreadsheet);
                $writer->save($url);
                break;
            case ExportType::ODS:
                $url = $path . DIRECTORY_SEPARATOR . "topic-export-$id.ods";
                $writer = new Ods($spreadsheet);
                $writer->save($url);
                break;
        }

        return new ExportData($url, $path);
    }

    /**
     * Check if there is something to export
     * @param string $id Id of the topic to be exported
     * @return bool If true, export is valid
     */
    public function hasExportData(string $id): bool
    {
        $query = $this->queryFactory->newSelect("topic");
        $query->select(["*"])
            ->andWhere(["session_id" => $id]);
        return $query->execute()->count() > 0;
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        $result = [
            "id" => $data->id ?? null,
            "title" => $data->title ?? null,
            "description" => $data->description ?? null,
            "subject" => $data->subject ?? null,
            "theme" => $data->theme ?? null,
            "visibility" => $data->visibility ?? 'private',
            "topic_activation" => $data->topicActivation ?? null,
            "connection_key" => $data->connectionKey ?? null,
            "max_participants" => $data->maxParticipants ?? null,
            "expiration_date" => $data->expirationDate ?? null,
            "public_screen_module_id" => $data->publicScreenModuleId ?? null,
            "allow_anonymous" => isset($data->allowAnonymous) ? $this->convertBoolToTinyInt($data->allowAnonymous) : null,
            "parameter" => isset($data->parameter) ? json_encode($data->parameter) : null
        ];

        if (property_exists($data, "creationDate")) {
            $result["creation_date"] = $data->creationDate;
        }

        return $result;
    }

    /**
     * Sets the active session task displayed on the public screen.
     * @param string $sessionId The session id to be updated.
     * @param string|null $taskId The active task id on the public screen.
     * @return object|null The updated session.
     * @throws GenericException
     */
    public function setPublicScreen(string $sessionId, ?string $taskId): object|null
    {
        $moduleId = null;
        if (isset($taskId)) {
            $moduleId = $this->getPossiblePublicScreenModule($sessionId, $taskId);
            if (is_null($moduleId)) {
                return null;
            }
        }

        $row = [
            "id" => $sessionId,
            "public_screen_module_id" => $moduleId
        ];
        return $this->update($row);
    }

    /**
     * Evaluates whether there is a valid module for the task to be set.
     * @param string $sessionId The session id to be updated.
     * @param string $taskId The active task id on the public screen.
     * @return string|null Module Id
     */
    public function getPossiblePublicScreenModule(string $sessionId, string $taskId): ?string
    {
        $query = $this->queryFactory->newSelect("module");
        $query->select(["module.id"])
            ->innerJoin("task", "task.id = module.task_id")
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->andWhere([
                "module.task_id" => $taskId,
                "topic.session_id" => $sessionId
            ]);

        $result = $query->execute()->fetch("assoc");
        if (is_array($result)) {
            return $result["id"];
        }
        return null;
    }

    /**
     * Check custom connection key.
     * @param string $connectionKey custom connection key
     * @return bool key is usable
     */
    public function isConnectionKeyFree(string $connectionKey): bool
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select("id")->andWhere(["connection_key" => $connectionKey]);
        if ($query->execute()->fetch("assoc")) {
            return false;
        }
        return true;
    }

    /**
     * Get the active session task displayed on the public screen.
     * @param string $sessionId The session id.
     * @return object|null The result entity.
     */
    public function getPublicScreen(string $sessionId): ?object
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["task.*", "module.id as module_id", "topic.order as topic_order"])
            ->innerJoin("module", "task.id = module.task_id")
            ->innerJoin("session", "module.id = session.public_screen_module_id")
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->andWhere([
                "session.id" => $sessionId
            ]);

        $result = $this->fetchAll($query, TaskData::class);

        if (is_array($result)) {
            if (sizeof($result) > 0) {
                $result = $result[0];
            } else {
                $result = null;
            }
        }

        if (is_object($result)) {
            $this->getDetails($result);
        }
        return $result;
    }

    /**
     * Get the active session task displayed on the public screen.
     * @param string $sessionId The session id.
     * @return ModificationData Modification Data
     */
    public function getPublicScreenModification(string $sessionId): ModificationData
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["task.modification_date"])
            ->innerJoin("module", "task.id = module.task_id")
            ->innerJoin("session", "module.id = session.public_screen_module_id")
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->andWhere([
                "session.id" => $sessionId
            ])
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Get list of connected modules
     * @param TaskData $data Task data
     */
    private function getDetails(TaskData $data): void
    {
        $moduleRepository = new ModuleRepository($this->queryFactory);
        /*if (isset($data->modules) && sizeof($data->modules) > 0)
            $data->modules = [$moduleRepository->getById($data->modules[0]->id)];
        else
            $data->modules = $moduleRepository->getAll($data->id);*/
        $data->modules = $moduleRepository->getAll($data->id);
    }

    /**
     * @param string $id The session id to clone
     * @param string $userId The id of the user
     * @return object|null The new session
     * @throws GenericException
     */
    /*public function clone(string $id, string $userId, ?string $newParentId): ?object
    {
        $topicRepository = new TopicRepository($this->queryFactory);
        $topicRepository->setAuthorisation($this->getAuthorisation());

        $session = $this->getById($id);

        if (is_null($session)) {
            throw new DomainException("Session not found");
        }

        $newSession = [
            "title" => $session->title,
            "description" => $session->description,
            "maxParticipants" => $session->maxParticipants,
            "expirationDate" => $session->expirationDate,
            "userId" => $userId,
        ];
        $newSession = $this->insert((object)$newSession);

        if (is_null($newSession) || !isset($newSession->id)) {
            throw new DomainException("Session not created");
        }

        $topics = $topicRepository->getAll($id);

        foreach ($topics as $topic) {
            if ($topic instanceof TopicData && !is_null($topic->id)) {
                $topicRepository->clone($topic->id, $userId, $newSession->id);
            }
        }

        return $newSession;
    }*/

    /**
     * create session from Template.
     * @param object $data The session data
     * @return object|null The new session
     */
    public function createFromTemplate(object $data): ?object
    {
        $templateId = $data->templateId;
        if (!isset($data->connectionKey))
            $data->connectionKey = $this->generateNewConnectionKey("connection_key");
        $data->creationDate = date("Y-m-d");
        $session = $this->genericInsert($data, false);

        if ($session->id) {
            $this->queryFactory->newInsert(
                'clone_helper',
                [
                    'target_id' => $session->id,
                    'source_id' => $templateId,
                    'table_name' => $this->getEntityName()
                ]
            )->execute();
        }
        $this->cloneDependencies($templateId, $session->id, false);

        return $session;
    }

    /**
     * Clone entity row.
     * @param string $oldId Old table primary key
     * @param string | null $newParentId New parent key value to be inserted
     * @param bool $cloneDependencies If false, ignore cloneDependencies function
     * @return string | null The new created entity id
     */
    public function clone(string $oldId, ?string $newParentId = null, bool $cloneDependencies = true): ?string
    {
        $connectionKey = $this->generateNewConnectionKey("connection_key");
        $newId = $this->queryFactory->newClone(
            $this->getEntityName(),
            ["id" => $oldId],
            $this->cloneColumns(),
            "connection_key",
            $connectionKey
        );

        if ($newId) {
            $this->queryFactory->newInsert(
                'clone_helper',
                [
                    'target_id' => $newId,
                    'source_id' => $oldId,
                    'table_name' => $this->getEntityName()
                ]
            )->execute();
        }

        if ($cloneDependencies && $newId) {
            $this->cloneDependencies($oldId, $newId);
        }
        return $newId;
    }

    /**
     * Include dependent data.
     * @param string $oldId Old table primary key
     * @param string $newId Old table primary key
     * @param bool $cloneRoles clone roles
     * @return void
     */
    protected function cloneDependencies(string $oldId, string $newId, bool $cloneRoles = true): void
    {
        if ($cloneRoles) {
            $this->queryFactory->newClone(
                "session_role",
                ["session_id" => $oldId],
                ["user_id", "role"],
                "session_id",
                $newId,
                false
            );
        } else {
            $authorisation = $this->getAuthorisation();
            $this->queryFactory->newInsert("session_role", [
                "session_id" => $newId,
                "user_id" => $authorisation->id,
                "role" => strtoupper(SessionRoleType::OWNER)
            ])->execute();
        }

        $topicRepository = new TopicRepository($this->queryFactory);
        $topicRepository->setAuthorisation($this->getAuthorisation());

        $rows_topic = $this->queryFactory->newSelect("topic")
            ->select([
                "id"
            ])
            ->andWhere([
                "session_id" => $oldId,
            ])
            ->execute()
            ->fetchAll("assoc");
        if (is_array($rows_topic) and sizeof($rows_topic) > 0) {
            foreach ($rows_topic as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $oldSubId = $reader->findString("id");
                if ($oldSubId) {
                    $topicRepository->clone($oldSubId, $newId);
                }
            }
        }

        $rows_task = $this->queryFactory->newSelect("task")
            ->select([
                "task.id",
            ])
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->andWhere([
                "topic.session_id" => $newId,
            ])
            ->execute()
            ->fetchAll("assoc");
        if (is_array($rows_task) and sizeof($rows_task) > 0) {
            $taskRepository = new TaskRepository($this->queryFactory);
            $taskRepository->setAuthorisation($this->getAuthorisation());
            foreach ($rows_task as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $taskId = $reader->findString("id");
                $taskRepository->correctParameter($taskId);
            }
        }

        $this->cleanupCloneHelper($newId);
    }

    private function cleanupCloneHelper(string $newId): void {
        /*$subQueryIdeas = $this->queryFactory->newSelect("selection_view")
            ->select(["selection_view.id"])
            ->innerJoin("topic", "topic.id = selection_view.topic_id")
            ->where(function ($exp, $q) {
                return $exp->equalFields("selection_view.id", "clone_helper.target_id");
            })
            ->andWhere(["topic.session_id" => $newId]);
        $this->queryFactory->newDelete("clone_helper")
            ->where(function ($exp, $q) use ($subQueryIdeas) {
                return $exp->exists($subQueryIdeas);
            })->execute();*/

        $subQuery = $this->queryFactory->newSelect("idea")
            ->select(["idea.id"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->where(function ($exp, $q) {
                return $exp->equalFields("idea.id", "clone_helper.target_id");
            })
            ->andWhere(["topic.session_id" => $newId]);
        $this->queryFactory->newDelete("clone_helper")
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->andWhere(["table_name = 'idea'"])->execute();

        $subQuery = $this->queryFactory->newSelect("module")
            ->select(["module.id"])
            ->innerJoin("task", "task.id = module.task_id")
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->where(function ($exp, $q) {
                return $exp->equalFields("module.id", "clone_helper.target_id");
            })
            ->andWhere(["topic.session_id" => $newId]);
        $this->queryFactory->newDelete("clone_helper")
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->andWhere(["table_name = 'module'"])->execute();

        $subQuery = $this->queryFactory->newSelect("task")
            ->select(["task.id"])
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->where(function ($exp, $q) {
                return $exp->equalFields("task.id", "clone_helper.target_id");
            })
            ->andWhere(["topic.session_id" => $newId]);
        $this->queryFactory->newDelete("clone_helper")
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->andWhere(["table_name = 'task'"])->execute();

        $subQuery = $this->queryFactory->newSelect("selection")
            ->select(["selection.id"])
            ->innerJoin("topic", "topic.id = selection.topic_id")
            ->where(function ($exp, $q) {
                return $exp->equalFields("selection.id", "clone_helper.target_id");
            })
            ->andWhere(["topic.session_id" => $newId]);
        $this->queryFactory->newDelete("clone_helper")
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->andWhere(["table_name = 'selection'"])->execute();

        $subQuery = $this->queryFactory->newSelect("topic")
            ->select(["topic.id"])
            ->where(function ($exp, $q) {
                return $exp->equalFields("topic.id", "clone_helper.target_id");
            })
            ->andWhere(["topic.session_id" => $newId]);
        $this->queryFactory->newDelete("clone_helper")
            ->where(function ($exp, $q) use ($subQuery) {
                return $exp->exists($subQuery);
            })
            ->andWhere(["table_name = 'topic'"])->execute();

        $this->queryFactory->newDelete("clone_helper")
            ->andWhere(["table_name = 'session'", "target_id" => $newId])->execute();
    }

    /**
     * List of columns to be cloned
     * @return array<string> The array
     */
    protected function cloneColumns(): array
    {
        return [
            "title",
            "description",
            "subject",
            "theme",
            "max_participants",
            "expiration_date",
            //"public_screen_module_id",
            "allow_anonymous",
            "parameter",
            "topic_activation"
        ];
    }
}
