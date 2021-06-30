<?php

namespace App\Domain\Base\Repository;

use App\Data\AuthorisationData;

/**
 * Trait sets the access role.
 */
trait AuthorisationTrait
{
    protected ?AuthorisationData $authorisation;

    /**
     * Sets the authorisation data for the repository.
     * @param AuthorisationData $authorisation Current authorisation data.
     * @return void
     */
    public function setAuthorisation(AuthorisationData $authorisation): void
    {
        $this->authorisation = $authorisation;
        if (isset($this->parentRepository)) {
            $this->getParentRepository()->setAuthorisation($authorisation);
        }
    }

    /**
     * Gets the authorisation data from the repository.
     * @return AuthorisationData
     * @throws GenericException
     */
    public function getAuthorisation(): AuthorisationData
    {
        if (isset($this->authorisation)) {
            return $this->authorisation;
        } else {
            throw new GenericException("Authorisation data not set.");
        }
    }
}
