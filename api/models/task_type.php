<?php
/**
 * @OA\Schema(
 *   description="possible task types",
 *   type="string",
 *   enum={"INFORMATION", "BRAINSTORMING", "SELECTION", "GROUPING", "VOTING"},
 *   example="BRAINSTORMING"
 * )
 */
class Task_Type {
  const INFORMATION = "information";
  const BRAINSTORMING = "brainstorming";
  const SELECTION = "selection";
  const GROUPING = "grouping";
  const VOTING = "voting";
}

?>
