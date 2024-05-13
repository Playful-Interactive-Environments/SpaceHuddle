<?php

namespace App\Domain\Topic\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Domain\Topic\Data\ExportData;
use App\Domain\Topic\Data\TopicData;
use App\Domain\Topic\Type\ExportType;
use App\Domain\Topic\Type\TopicState;
use App\Domain\Topic\Type\ViewType;
use App\Factory\QueryFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Selective\ArrayReader\ArrayReader;

/**
 * Repository
 */
class TopicRepository implements RepositoryInterface
{
    use RepositoryTrait;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->setUp(
            $queryFactory,
            "topic",
            TopicData::class,
            "session_id",
            SessionRepository::class
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param string|null $detailEntity Detail entity which should be modified
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(
        ?string $id,
        string | null $detailEntity = null
    ): ?string {
        return $this->getAuthorisationRoleForState(
            $id,
            [
                strtoupper(TopicState::ACTIVE)
            ],
            $detailEntity
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param array $validStates Valid states
     * @param string|null $detailEntity Detail entity which should be modified
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    private function getAuthorisationRoleForState(
        ?string $id,
        array $validStates,
        string | null $detailEntity = null
    ): ?string {
        $authorisation = $this->getAuthorisation();
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere(["id" => $id]);

        if (
            $authorisation->isParticipant()
        ) {
            $query->whereInList("state", $validStates);
        }

        return $this->getAuthorisationRoleFromQuery($id, $query);
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return object|array<object>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|object|array
    {
        if (count($sortConditions) == 0) {
            $sortConditions = ["order"];
        }

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["*"])
            ->andWhere($conditions)
            ->order($sortConditions);

        $authorisation = $this->getAuthorisation();
        if ($authorisation->isParticipant()) {
            $query->innerJoin("participant_topic", [
                "participant_topic.id = topic.id",
                "participant_topic.participant_id" => $authorisation->id
            ]);
        }

        return $this->fetchAll($query);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["id"]);
        $query->andWhere(["topic_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $task = new TaskRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $taskId = $resultItem["id"];
                $task->deleteById($taskId);
            }
        }

        $query = $this->queryFactory->newSelect("selection");
        $query->select(["id"]);
        $query->andWhere(["topic_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            $selection = new SelectionRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $selectionId = $resultItem["id"];
                $selection->deleteById($selectionId);
            }
        }
    }

    /**
     * Export all topic data as spread sheet
     * @param string $id Id of the topic to be exported
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

        $this->fillSpreadsheet($spreadsheet, $id, $path);
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
     * Fill spreadsheet with export data of all topics
     * @param Spreadsheet $spreadsheet spreadsheet
     * @param string $id Id of the topic to be exported
     * @param string $path Saving path
     * @throws Exception|\PhpOffice\PhpSpreadsheet\Exception
     */
    public function fillSpreadsheet(Spreadsheet $spreadsheet, string $id, string $path): void
    {
        //get task
        $query = $this->queryFactory->newSelect("task");
        $query->select(["*"])
            ->andWhere(["topic_id" => $id])
            ->order("order");
        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            foreach ($rows as $index => $taskItem) {
                $reader = new ArrayReader($taskItem);
                $detailId = $reader->findString("id");
                $name = $reader->findString("name");
                $taskType = $reader->findString("task_type");
                if ($name) {
                    $name = preg_replace('/[^a-zA-Z0-9]/', ' ', $name);
                }

                $detailRows = false;
                $exportColumns = [];

                $detailQuery = $this->queryFactory->newSelect("selection_view");
                $detailQuery
                    ->innerJoin("selection_view_idea", [
                        "selection_view_idea.parent_id = selection_view.id",
                        "selection_view_idea.type = selection_view.type"
                    ])
                    ->innerJoin("idea", "selection_view_idea.idea_id = idea.id")
                    ->andWhere(["selection_view.task_id" => $detailId]);

                if (strtolower($taskType) == TaskType::VOTING) {
                    $detailQuery->select([
                        "idea.id",
                        "idea.keywords",
                        "idea.description",
                        "idea.image_timestamp",
                        "idea.id AS image_id",
                        "idea.link",
                        "idea.order",
                        "idea.parameter",
                        "idea.participant_id",
                        "idea.state",
                        "idea.task_id",
                        "idea.timestamp",
                        "vote_result.count_rating as count",
                        "vote_result.sum_detail_rating as sum"
                    ])
                        ->innerJoin("vote_result", "vote_result.idea_id = idea.id")
                        ->order("selection_view_idea.order");
                    $detailRows = $detailQuery->execute()->fetchAll("assoc");
                    $exportColumns = ["keywords", "description", "image", "link", "count", "sum"];
                } elseif (strtolower($taskType) == TaskType::CATEGORISATION) {
                    $detailQuery->select([
                        "idea.id",
                        "idea.keywords",
                        "idea.description",
                        "idea.image_timestamp",
                        "idea.id AS image_id",
                        "idea.link",
                        "idea.order",
                        "idea.parameter",
                        "idea.participant_id",
                        "idea.state",
                        "idea.task_id",
                        "idea.timestamp",
                        "category.keywords as category_keywords",
                        "category.description as category_description",
                        "category.id as category_image_id",
                        "category.link as category_link",
                        "category.id as category_id"
                    ])
                        ->join([
                            "category" => [
                                "table" => "idea",
                                "type" => "INNER",
                                "conditions" => [
                                    "selection_view_idea.parent_id = category.id",
                                    "selection_view_idea.type" => strtoupper(ViewType::HIERARCHY)
                                ]
                            ]
                        ])
                        ->order(["category.keywords", "selection_view_idea.order"]);
                    $detailRows = $detailQuery->execute()->fetchAll("assoc");
                    $exportColumns = [
                        "category_keywords",
                        "category_description",
                        "category_image",
                        "category_link",
                        "keywords",
                        "description",
                        "image",
                        "link"
                    ];
                } elseif (
                    strtolower($taskType) == TaskType::BRAINSTORMING ||
                    strtolower($taskType) == TaskType::SELECTION
                ) {
                    $detailQuery->select([
                        "idea.id",
                        "idea.keywords",
                        "idea.description",
                        "idea.image_timestamp",
                        "idea.id AS image_id",
                        "idea.link",
                        "idea.order",
                        "idea.parameter",
                        "idea.participant_id",
                        "idea.state",
                        "idea.task_id",
                        "idea.timestamp"
                    ])
                        ->order("selection_view_idea.order");
                    $detailRows = $detailQuery->execute()->fetchAll("assoc");
                    $exportColumns = ["keywords", "description", "image", "link"];
                } elseif (strtolower($taskType) == TaskType::INFORMATION) {
                    $moduleQuery = $this->queryFactory->newSelect("module")
                        ->select(["module_name"])
                        ->andWhere(["task_id" => $detailId, "module_name IN" => ["quiz", "survey", "talk"]]);
                    $isQuiz = $moduleQuery->execute()->rowCount() > 0;
                    if ($isQuiz) {
                        $detailQuery->select([
                            "idea.id",
                            "idea.keywords",
                            "idea.description",
                            "idea.image_timestamp",
                            "idea.id AS image_id",
                            "idea.link",
                            "idea.order",
                            "idea.parameter",
                            "idea.participant_id",
                            "idea.state",
                            "idea.task_id",
                            "idea.timestamp",
                            "category.keywords as question_keywords",
                            "category.description as question_description",
                            "category.id as question_image_id",
                            "category.link as question_link",
                            "category.id as question_id",
                            "vote_result.count_rating as count",
                            "vote_result.sum_detail_rating as sum"
                        ])
                            ->join([
                                "category" => [
                                    "table" => "idea",
                                    "type" => "INNER",
                                    "conditions" => [
                                        "selection_view_idea.parent_id = category.id",
                                        "selection_view_idea.type" => strtoupper(ViewType::HIERARCHY)
                                    ]
                                ]
                            ])
                            ->leftJoin("vote_result", "vote_result.idea_id = idea.id")
                            ->order(["category.order", "selection_view_idea.order"]);
                        $detailRows = $detailQuery->execute()->fetchAll("assoc");
                        $exportColumns = [
                            "question_keywords",
                            "question_description",
                            "question_image",
                            "question_link",
                            "keywords",
                            "description",
                            "image",
                            "link",
                            "count",
                            "sum"
                        ];
                    } else {
                        $detailQuery->select([
                            "idea.id",
                            "idea.keywords",
                            "idea.description",
                            "idea.image_timestamp",
                            "idea.id AS image_id",
                            "idea.link",
                            "idea.order",
                            "idea.parameter",
                            "idea.participant_id",
                            "idea.state",
                            "idea.task_id",
                            "idea.timestamp"
                        ])
                            ->order("selection_view_idea.order");
                        $detailRows = $detailQuery->execute()->fetchAll("assoc");
                        $exportColumns = ["keywords", "description", "image", "link"];
                    }
                }

                if (sizeof($exportColumns) > 0) {
                    $sheet = $spreadsheet->createSheet();
                    if ($name) {
                        $sheet->setTitle(mb_substr($name, 0, 31));
                    }

                    $alphas = range('A', 'Z');
                    foreach ($exportColumns as $columnIndex => $columnName) {
                        $sheet->setCellValue("$alphas[$columnIndex]1", $columnName);
                        $styleArray = [
                            'font' => [
                                'bold' => true,
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                            ],
                            'borders' => [
                                'bottom' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                ],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'color' => [
                                    'argb' => 'FFA0A0A0',
                                ],
                            ],
                        ];
                        $sheet->getStyle("$alphas[$columnIndex]1")->applyFromArray($styleArray);
                    }
                    $rowNumber = 1;
                    if (is_array($detailRows) and sizeof($detailRows) > 0) {
                        foreach ($detailRows as $detailIndex => $detailItem) {
                            $rowNumber = $detailIndex + 2;
                            $detailReader = new ArrayReader($detailItem);
                            foreach ($exportColumns as $columnIndex => $columnName) {
                                $columnLetter = $alphas[$columnIndex];
                                $detailValue = $detailReader->findString($columnName);
                                $hasImage = false;
                                if (str_contains($columnName, "image") !== false) {
                                    $imageId = $detailReader->findString($columnName . "_id");
                                    $queryImage = $this->queryFactory->newSelect("idea");
                                    $queryImage->select(["image"])
                                        ->andWhere(["id" => $imageId]);
                                    $imageRows = $queryImage->execute()->fetchAll("assoc");
                                    if (is_array($rows) and sizeof($rows) > 0) {
                                        $imageReader = new ArrayReader($imageRows[0]);
                                        $detailValue = $imageReader->findString("image");
                                    }
                                    if ($detailValue) {
                                        $imageName = $detailReader->findString("category_id");
                                        if (!$imageName || $columnName == "image") {
                                            $imageName = $detailReader->findString("id");
                                        }
                                        $imageDescription = $detailReader->findString("keywords");
                                        $imagePath = $this->convertBase64ToImage(
                                            $detailValue,
                                            $path . DIRECTORY_SEPARATOR,
                                            $imageName
                                        );

                                        if ($imagePath != "") {
                                            $drawing = new Drawing();
                                            $drawing->setName($imageName);
                                            $drawing->setDescription($imageDescription);
                                            $drawing->setPath($imagePath);
                                            $drawing->setCoordinates("$columnLetter$rowNumber");
                                            $drawing->setWidth(200);
                                            $drawing->setWorksheet($sheet);
                                            $actualRowHeight = $sheet->getRowDimension($rowNumber)->getRowHeight("px");
                                            if ($actualRowHeight < $drawing->getHeight()) {
                                                $sheet->getRowDimension($rowNumber)
                                                    ->setRowHeight($drawing->getHeight(), "px");
                                            }
                                            $hasImage = true;
                                        }
                                    }
                                }

                                if (!$hasImage) {
                                    $sheet->setCellValue("$columnLetter$rowNumber", $detailValue);
                                    if (strpos($columnName, "link") !== false && $detailValue) {
                                        $sheet->getCell("$columnLetter$rowNumber")->getHyperlink()->setUrl($detailValue);
                                    }
                                }
                            }
                        }
                    }

                    foreach ($exportColumns as $columnIndex => $columnName) {
                        $columnLetter = $alphas[$columnIndex];
                        if (strpos($columnName, "image") !== false) {
                            $sheet->getColumnDimension($columnLetter)->setWidth(200, "px");
                        } elseif (strpos($columnName, "description") !== false) {
                            $sheet->getColumnDimension($columnLetter)->setWidth(300, "px");
                            $sheet->getStyle($columnLetter."1:".$columnLetter.$rowNumber)
                                ->getAlignment()->setWrapText(true);
                            $sheet->setSelectedCell("A1");
                        } elseif (strpos($columnName, "link") !== false) {
                            $sheet->getColumnDimension($columnLetter)->setWidth(300, "px");
                            $sheet->getStyle($columnLetter."1:".$columnLetter.$rowNumber)
                                ->getAlignment()->setWrapText(true);
                            if ($rowNumber > 1) {
                                $sheet->getStyle($columnLetter."2:".$columnLetter.$rowNumber)
                                    ->getFont()->getColor()->setARGB("FF0000FF");
                            }
                            $sheet->setSelectedCell("A1");
                        } else {
                            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                        }
                    }
                }
            }
        }
    }

    /**
     * Check if there is something to export
     * @param string $id Id of the topic to be exported
     * @return bool If true, export is valid
     */
    public function hasExportData(string $id): bool
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["*"])
            ->andWhere(["topic_id" => $id]);
        return $query->execute()->count() > 0;
    }

    /**
     * convert base64 string to image
     * @param string $base64Code Base64 coded image
     * @param string $path Saving path
     * @param string|null $imageName Image title without extension
     * @return string Result path
     */
    private function convertBase64ToImage(string $base64Code, string $path, string $imageName = null): string
    {
        if (!empty($base64Code) && !empty($path)) {
            // split the string to get extension and remove not required part
            // $stringPieces[0] = to get image extension
            // $stringPieces[1] = actual string to convert into image
            $stringPieces = explode(";base64,", $base64Code);

            /*@ Get type of image ex. png, jpg, etc. */
            // $imageType[1] will return type
            $imageTypePieces = explode("image/", $stringPieces[0]);

            if (sizeof($imageTypePieces) < 2) {
                return "";
            }

            $imageType = $imageTypePieces[1];

            /*@ Create full path with image name and extension */
            $storeAt = $path.md5(uniqid()).'.'.$imageType;

            /*@ If image name available then use that  */
            if (!empty($imageName)) {
                $storeAt = $path . $imageName . '.' . $imageType;
            }

            $decodedString = base64_decode($stringPieces[1]);
            file_put_contents($storeAt, $decodedString);

            return $storeAt;
        }
        return "";
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        return [
            "id" => $data->id ?? null,
            "session_id" => $data->sessionId ?? null,
            "title" => $data->title ?? null,
            "description" => $data->description ?? null,
            "order" => $data->order ?? 0,
            "state" => $data->state ?? strtoupper(TopicState::ACTIVE)
        ];
    }

    /**
     * @throws GenericException
     * @throws Exception
     */
    /*public function clone(string $id, string $userId, ?string $newParentId): object
    {
        $taskRepository = new TaskRepository($this->queryFactory);
        $moduleRepository = new ModuleRepository($this->queryFactory);

        $taskRepository->setAuthorisation($this->getAuthorisation());
        $moduleRepository->setAuthorisation($this->getAuthorisation());

        $selectionRepository = new SelectionRepository($this->queryFactory);
        $selectionRepository->setAuthorisation($this->getAuthorisation());

        $topic = $this->getById($id);

        if (!$topic instanceof TopicData) {
            throw new Exception("Topic not found");
        }

        $order = $newParentId ? $topic->order : $this->getMaxOrder($topic->sessionId) + 1;

        $newTopic = [
            "title" => $topic->title,
            "description" => $topic->description,
            "userId" => $userId,
            "order" => $order,
            "sessionId" => $newParentId ?? $topic->sessionId,
        ];
        $newTopic = $this->insert((object)$newTopic);
        if (!$newTopic instanceof TopicData) {
            throw new Exception("Topic not created");
        }

        // clone and remember all selections
        $selections = $selectionRepository->getAll($id);
        $newSelections = [];
        foreach ($selections as $selection) {
            $newSelection = [
                "topicId" => $newTopic->id,
                "name" => $selection->name,
            ];
            $newSelection = $selectionRepository->insert((object)$newSelection);
            $newSelections[$selection->id] = $newSelection->id;
        }

        // clone and remember all tasks
        $tasks = $taskRepository->getAll($topic->id ?? "");
        $newTasks = [];
        for ($j = 0; $j < sizeof($tasks); $j++) {
            if (!$tasks[$j] instanceof TaskData) {
                throw new Exception("Task not found");
            }

            $newTask = [
                "taskType" => $tasks[$j]->taskType,
                "name" => $tasks[$j]->name,
                "description" => $tasks[$j]->description,
                "order" => $tasks[$j]->order,
                "parameter" => $tasks[$j]->parameter ?? "{}",
                "userId" => $userId,
                "topicId" => $newTopic->id,
                "state" => $tasks[$j]->state,
            ];
            $newTask = $taskRepository->insert((object)$newTask, false);

            $newTasks[$tasks[$j]->id] = $newTask;

            // Clone modules
            $needsIdeasCloned = strtolower($newTask->taskType ?? "") === TaskType::INFORMATION;
            //$modules = $moduleRepository->getAll($tasks[$j]->id ?? "");

            foreach ($tasks[$j]->modules as $module) {
                $newModule = [
                    "taskId" => $newTask->id,
                    "name" => $module->name,
                    "order" => $module->order,
                    "state" => $module->state,
                    "syncPublicParticipant" => $module->syncPublicParticipant,
                    "parameter" => $module->parameter ?? null,
                    "userId" => $userId,
                ];
                $newModule = $moduleRepository->insert((object)$newModule);
            }

            if (!$needsIdeasCloned) {
                continue;
            }

            // Clone ideas if applicable
            $ideaRepository = new IdeaRepository($this->queryFactory);
            $ideaRepository->setAuthorisation($this->getAuthorisation());
            $ideas = $ideaRepository->getAll($tasks[$j]->id);
            $ideas = array_values(array_filter($ideas, function ($idea) {
                return $idea->participantId == null;
            }));

            $hierarchyRepository = new HierarchyRepository($this->queryFactory);
            $hierarchyRepository->setAuthorisation($this->getAuthorisation());
            $newIdeas = [];
            $hierarchiesToCreate = [];
            for ($k = 0; $k < sizeof($ideas); $k++) {
                $newIdeaId = $ideaRepository->clone($ideas[$k]->id, $newTask->id);


                // $newIdea = [
                //     "taskId" => $newTask->id,
                //     "keywords" => $ideas[$k]->keywords,
                //     "description" => $ideas[$k]->description,
                //     "parameter" => $ideas[$k]->parameter ?? null,
                //     "link" => $ideas[$k]->link,
                //     "order" => $ideas[$k]->order,
                //     "state" => $ideas[$k]->state,
                //     "userId" => $userId,
                // ];
                // $newIdea = $ideaRepository->insert((object)$newIdea);
                $newIdeas[] = $newIdeaId;
                $hierarchies = $hierarchyRepository->get(["hierarchy.category_idea_id" => $ideas[$k]->id]) ?? [];
                if (!is_array($hierarchies)) $hierarchies = [$hierarchies];
                foreach ($hierarchies as $hierarchy) {
                    $lowerIdx = $this->findIndex($hierarchy->id, $ideas);
                    if ($lowerIdx === -1) {
                        // The idea was not found, meaning it was an answer provided by a participant
                        continue;
                    }

                    $hierarchiesToCreate[] = [
                        "upperId" => $newIdea,
                        "lowerIdx" => $lowerIdx,
                        "order" => $hierarchy->order,
                    ];
                }
            }

            // All ideas have been cloned, now clone hierarchies
            foreach ($hierarchiesToCreate as $hierarchy) {
                $this->queryFactory->newInsert(
                    "hierarchy",
                    [
                        "sub_idea_id" => $newIdeas[$hierarchy["lowerIdx"]],
                        "category_idea_id" => $hierarchy["upperId"],
                        "order" => $hierarchy["order"]
                    ]
                )->execute();
            }
        }

        // Correct all parameters of tasks
        foreach ($newTasks as $task) {
            $parameter = json_encode($task->parameter);
            if (!is_string($parameter) || strlen($parameter) === 0) {
                continue;
            }
            foreach ($newTasks as $oldId => $replaceTask) {
                $parameter = str_replace($oldId, $replaceTask->id, $parameter);
            }
            foreach ($newSelections as $oldId => $replaceSelection) {
                $parameter = str_replace($oldId, $replaceSelection, $parameter);
            }
            $this->queryFactory
                ->newUpdate("task", ["parameter" => $parameter])
                ->andWhere(["id" => $task->id])
                ->execute();
        }

        return $newTopic;
    }*/

    /**
     * @param string $id The id to search for
     * @param array $array The array to search in
     * @return int The index of the found id within the array
     */
    private function findIndex(?string $id, array $array): int
    {
        if ($id === null) {
            return -1;
        }

        for ($i = 0; $i < sizeof($array); $i++) {
            if ($array[$i]->id === $id) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * @param ?string $sessionId The id of the session
     * @return int The maximum order of any topic within the given session
     */
    private function getMaxOrder(?string $sessionId): int
    {
        if ($sessionId === null) {
            return 0;
        }

        $maxOrder = $this->queryFactory->newSelect("topic")
            ->select("MAX(`order`) as maxOrder")
            ->andWhere(["session_id" => $sessionId])
            ->execute()
            ->fetch("assoc")["maxOrder"];
        return $maxOrder ?? 0;
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
        $newId = $this->queryFactory->newClone(
            $this->getEntityName(),
            ["id" => $oldId],
            $this->cloneColumns(),
            $this->parentIdName,
            $newParentId
        );

        $selectParentId = $this->queryFactory->newQuery()->from($this->getEntityName())
            ->select([$this->parentIdName])
            ->where(["id" => $oldId]);

        $select = $this->queryFactory->newQuery()->from($this->getEntityName())
            ->select(["(count(*) - 1) as count"])
            ->where([$this->parentIdName => $selectParentId]);

        $this->queryFactory->newUpdate($this->getEntityName(), ["order" => $select])
            ->andWhere(["id" => $newId])
            ->execute();

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
     * @return void
     */
    protected function cloneDependencies(string $oldId, string $newId): void
    {
        $taskRepository = new TaskRepository($this->queryFactory);
        $taskRepository->setAuthorisation($this->getAuthorisation());

        $selectionRepository = new SelectionRepository($this->queryFactory);
        $selectionRepository->setAuthorisation($this->getAuthorisation());

        $rows_selection = $this->queryFactory->newSelect("selection")
            ->select([
                "id"
            ])
            ->andWhere([
                "topic_id" => $oldId,
            ])
            ->execute()
            ->fetchAll("assoc");
        $selectionMapping = [];
        if (is_array($rows_selection) and sizeof($rows_selection) > 0) {
            foreach ($rows_selection as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $oldSubId = $reader->findString("id");
                if ($oldSubId) {
                    $newSubId = $selectionRepository->clone($oldSubId, $newId, false);
                    $selectionMapping[$oldSubId] = $newSubId;
                }
            }
        }

        $rows_task = $this->queryFactory->newSelect("task")
            ->select([
                "id",
                "parameter"
            ])
            ->andWhere([
                "topic_id" => $oldId,
            ])
            ->execute()
            ->fetchAll("assoc");
        $taskMappingList = [];
        //$taskMapping = [];
        //$taskParameter = [];
        if (is_array($rows_task) and sizeof($rows_task) > 0) {
            foreach ($rows_task as $resultItem) {
                $reader = new ArrayReader($resultItem);
                $oldTaskId = $reader->findString("id");
                if ($oldTaskId) {
                    $newTaskId = $taskRepository->clone($oldTaskId, $newId);
                    array_push($taskMappingList, $newTaskId);
                    //$taskMapping[$oldTaskId] = $newTaskId;
                    //$taskParameter[$newTaskId] = $reader->findString("parameter");
                }
            }
        }

        // Correct all parameters of tasks
        foreach ($taskMappingList as $key) {
            $taskRepository->correctParameter($key);
        }
        /*foreach ($taskParameter as $newTaskId => $parameter) {
            if (!is_string($parameter) || strlen($parameter) === 0) {
                continue;
            }
            echo "$newTaskId:\n";
            echo json_encode($parameter);
            echo "\n\n";
            foreach ($taskMapping as $oldTaskId => $replaceTaskId) {
                $parameter = str_replace($oldTaskId, $replaceTaskId, $parameter);
            }
            echo json_encode($parameter);
            echo "\n\n";
            foreach ($selectionMapping as $oldId => $replaceSelectionId) {
                $parameter = str_replace($oldId, $replaceSelectionId, $parameter);
            }
            echo json_encode($parameter);
            echo "\n\n";
            $this->queryFactory
                ->newUpdate("task", ["parameter" => $parameter])
                ->andWhere(["id" => $newTaskId])
                ->execute();
        }*/
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
            "order",
            //"active_task_id",
            "state"
        ];
    }
}
