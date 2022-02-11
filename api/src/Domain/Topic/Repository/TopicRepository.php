<?php

namespace App\Domain\Topic\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskType;
use App\Domain\Topic\Data\ExportData;
use App\Domain\Topic\Data\TopicData;
use App\Domain\Topic\Type\ExportType;
use App\Domain\Topic\Type\ViewType;
use App\Factory\QueryFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
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
        $path = "export\\$id";
        if (!is_dir($path)) {
            mkdir($path);
        } else {
            $files = glob("$path\\*", GLOB_MARK);
            foreach ($files as $file) {
                if (!is_dir($file)) {
                    unlink($file);
                }
            }
        }
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        //get task
        $query = $this->queryFactory->newSelect("task");
        $query->select(["*"])
            ->andWhere(["topic_id" => $id])
            ->order("order");
        $rows = $query->execute()->fetchAll("assoc");
        if (is_array($rows) and sizeof($rows) > 0) {
            foreach ($rows as $index => $taskItem) {
                $sheet = $spreadsheet->createSheet($index);
                $reader = new ArrayReader($taskItem);
                $detailId = $reader->findString("id");
                $name = $reader->findString("name");
                $taskType = $reader->findString("task_type");
                if ($name) {
                    $sheet->setTitle(substr($name, 0, 31));
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
                        "idea.*",
                        "vote_result.count_rating as count",
                        "vote_result.sum_detail_rating as sum"
                    ])
                        ->innerJoin("vote_result", "vote_result.idea_id = idea.id")
                        ->order("selection_view_idea.order");
                    $detailRows = $detailQuery->execute()->fetchAll("assoc");
                    $exportColumns = ["keywords", "description", "image", "link", "count", "sum"];
                } elseif (strtolower($taskType) == TaskType::CATEGORISATION) {
                    $detailQuery->select([
                        "idea.*",
                        "category.keywords as category_keywords",
                        "category.description as category_description",
                        "category.image as category_image",
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
                } else {
                    $detailQuery->select(["idea.*"])
                        ->order("selection_view_idea.order");
                    $detailRows = $detailQuery->execute()->fetchAll("assoc");
                    $exportColumns = ["keywords", "description", "image", "link"];
                }

                $alphas = range('A', 'Z');
                foreach ($exportColumns as $columnIndex => $columnName) {
                    $sheet->setCellValue("$alphas[$columnIndex]1", $columnName);
                }
                if (is_array($detailRows) and sizeof($detailRows) > 0) {
                    foreach ($detailRows as $detailIndex => $detailItem) {
                        $rowNumber = $detailIndex + 2;
                        $detailReader = new ArrayReader($detailItem);
                        foreach ($exportColumns as $columnIndex => $columnName) {
                            $columnLetter = $alphas[$columnIndex];
                            $detailValue = $detailReader->findString($columnName);
                            if (strpos($columnName, "image") !== false && $detailValue) {
                                $imageName = $detailReader->findString("category_id");
                                if (!$imageName || $columnName == "image") $imageName = $detailReader->findString("id");
                                $imageDescription = $detailReader->findString("keywords");
                                $imagePath = $this->convertBase64ToImage($detailValue, "$path\\", $imageName);

                                $drawing = new Drawing();
                                $drawing->setName($imageName);
                                $drawing->setDescription($imageDescription);
                                $drawing->setPath($imagePath);
                                $drawing->setCoordinates("$columnLetter$rowNumber");
                                $drawing->setWidth(200);
                                $drawing->setWorksheet($sheet);
                                $actualRowHeight = $sheet->getRowDimension($rowNumber)->getRowHeight("px");
                                if ($actualRowHeight < $drawing->getHeight())
                                    $sheet->getRowDimension($rowNumber)
                                        ->setRowHeight($drawing->getHeight(), "px");
                            } else {
                                $sheet->setCellValue("$columnLetter$rowNumber", $detailValue);
                            }
                        }
                    }
                }

                foreach ($exportColumns as $columnIndex => $columnName) {
                    $columnLetter = $alphas[$columnIndex];
                    if (strpos($columnName, "image") !== false) {
                        $sheet->getColumnDimension($columnLetter)->setWidth(200, "px");
                    } else {
                        $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
                    }
                }
            }
        }

        $url = null;
        if (strtolower($exportType) == ExportType::XLSX) {
            $url = "$path\\topic-export-$id.xlsx";
            $writer = new Xlsx($spreadsheet);
            $writer->save($url);
        }
        if ($url) {
            $exportPath = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] .
                str_replace("index.php", "", $_SERVER["PHP_SELF"]);
            $url = str_replace("\\", "/", $url);
            $url = "$exportPath$url";
        }
        return new ExportData($url);
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
            "order" => $data->order ?? 0
        ];
    }
}
