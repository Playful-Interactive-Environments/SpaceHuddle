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
     * @return object|null First entry
     */
    private function getFirstEntity(string $entityUrl, ?array $postData = null, ?string $token = null) : ?object
    {
        $request = $this->createJsonRequest(
            "GET",
            "/$entityUrl/"
        );
        $request = $this->withJwtAuth($request, $token);
        $response = $this->app->handle($request);
        $json = json_decode($response->getBody());
        $result = null;
        if (is_array($json) and sizeof($json) > 0 and property_exists($json[0], "id")) {
            $result = $json[0];
        } elseif (isset($postData)) {
            $entityNameSingular = substr_replace($entityUrl ,"", -1);
            $request = $this->createJsonRequest(
                "POST",
                "/$entityNameSingular/",
                $postData
            );
            $request = $this->withJwtAuth($request);
            $this->app->handle($request);
            $result = $this->getFirstEntity($entityUrl, null);
        }
        return $result;
    }
}
