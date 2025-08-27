<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Validation;

use App\Shared\Context\Api\Errors\GatewayError;
use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Exception\ApiValidationException;

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
     * @throws ApiValidationException
     */
    protected function validateUnrecognizedRequestPayload(string ...$accepted): static
    {
        $unrecognised = $this->request->payload->getUnrecognizedKeys(...$accepted);
        if (!empty($unrecognised)) {
            throw new ApiValidationException(GatewayError::UNRECOGNIZED_REQUEST_PAYLOAD, baggage: $unrecognised);
        }

        return $this;
    }
}