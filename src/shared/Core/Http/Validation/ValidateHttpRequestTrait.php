<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Validation;

use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Exception\HttpUnrecognizedPayloadException;

/**
 * Trait ValidateHttpRequestTrait
 * @package App\Shared\Core\Http\Validation
 * @mixin AppAwareEndpoint
 */
trait ValidateHttpRequestTrait
{
    /**
     * @param string ...$accepted
     * @return $this
     * @throws HttpUnrecognizedPayloadException
     */
    protected function validateUnrecognizedRequestPayload(string ...$accepted): static
    {
        $unrecognised = $this->request->payload->getUnrecognizedKeys(...$accepted);
        if (!empty($unrecognised)) {
            throw new HttpUnrecognizedPayloadException("HTTP request contains unrecognized payload keys", $unrecognised);
        }

        return $this;
    }
}