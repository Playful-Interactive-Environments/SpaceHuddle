<?php

namespace App\Test\Traits;

/**
 * Entity Test Trait.
 */
trait EntityTestTrait
{
    /**
     * Get first entity entry from the database
     * @param string $entityUrl Name of the get api call
     * @param array|null $postData If set, creates a new entry with the specified data if the result is empty
     * @param string|null $token Use alternative Login
     * @param array $condition The WHERE conditions to add with AND.
     * @return object|null First entry
     */
    private function getFirstEntity(
        string $entityUrl,
        ?array $postData = null,
        ?string $token = null,
        array $condition = []
    ) : ?object {
        $request = $this->createJsonRequest(
            "GET",
            "/$entityUrl/"
        );
        $request = $this->withJwtAuth($request, $token);
        $response = $this->app->handle($request);
        $json = json_decode($response->getBody());
        $result = null;
        if (is_array($json) and sizeof($json) > 0 and property_exists($json[0], "id")) {
            if (sizeof($condition) > 0) {
                foreach ($json as $jsonItem) {
                    $conditionIsTrue = true;
                    foreach ($condition as $conditionKey => $conditionValue) {
                        if (strtoupper($jsonItem->$conditionKey) != strtoupper($conditionValue)) {
                            $conditionIsTrue = false;
                            break;
                        }
                    }
                    if ($conditionIsTrue) {
                        $result = $jsonItem;
                        break;
                    }
                }
            } else {
                $result = $json[0];
            }
        }

        if (is_null($result) and isset($postData)) {
            $entityNameSingular = substr_replace($entityUrl ,"", -1);
            $request = $this->createJsonRequest(
                "POST",
                "/$entityNameSingular/",
                $postData
            );
            $request = $this->withJwtAuth($request, $token);
            $this->app->handle($request);
            $result = $this->getFirstEntity($entityUrl, null, $token, $condition);
        }
        return $result;
    }
}
