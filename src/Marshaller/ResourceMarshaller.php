<?php


namespace Seier\Resting\Marshaller;


use Seier\Resting\UnionResource;
use Seier\Resting\ResourceFactory;
use Seier\Resting\Fields\ResourceField;
use Seier\Resting\Fields\ResourceArrayField;
use Seier\Resting\Resource as RestingResource;
use Seier\Resting\Parsing\DefaultParseContext;
use Seier\Resting\Validation\Errors\ValidationError;
use Seier\Resting\ResourceValidation\ResourceValidator;
use Seier\Resting\Exceptions\ValidationExceptionHandler;
use Seier\Resting\Validation\Errors\NotArrayValidationError;
use Seier\Resting\Validation\Errors\RequiredValidationError;
use Seier\Resting\Validation\Errors\NullableValidationError;
use Seier\Resting\Validation\Predicates\ArrayResourceContext;
use Seier\Resting\Validation\Errors\ForbiddenValidationError;
use Seier\Resting\Validation\Errors\UnknownUnionDiscriminatorValidationError;

class ResourceMarshaller
{

    private array $validationErrors = [];
    private array $currentPath = [];
    private bool $isStringBased = false;

    public function isStringBased(bool $allow = true): self
    {
        $this->isStringBased = $allow;

        return $this;
    }

    private function pushError(ValidationError $validationError)
    {
        $this->validationErrors[] = $validationError;
    }

    public function pushPathError(string $path, ValidationError $validationError)
    {
        $this->validationErrors[] = $validationError->prependPath($path);
    }

    private function pushRootError(NotArrayValidationError $validationError)
    {
        $this->pushPathError('', $validationError);
    }

    public function pushPath(string $path)
    {
        array_push($this->currentPath, $path);
    }

    private function popPath()
    {
        array_pop($this->currentPath);
    }

    private function reset()
    {
        $this->validationErrors = [];
        $this->currentPath = [];
    }

    public function marshalNullableResource(ResourceFactory $factory, $content): ResourceMarshallerResult
    {
        if ($content === null) {
            return new ResourceMarshallerResult(null, []);
        }

        return $this->marshalResource($factory, $content);
    }

    public function marshalResource(ResourceFactory $factory, mixed $content): ResourceMarshallerResult
    {
        $this->reset();

        $resource = $factory->create();

        if (!is_array($content)) {
            $this->pushRootError(new NotArrayValidationError($content));
            return new ResourceMarshallerResult($resource, $this->validationErrors);
        }

        if ($resource instanceof UnionResource) {
            $resource = $this->getUnionSubResource($resource, $content);
            if ($resource instanceof ResourceMarshallerResult) {
                return $resource;
            }
        }

        $this->marshalResourceFields($resource, $content);

        return new ResourceMarshallerResult($resource, $this->validationErrors);
    }

    private function getUnionSubResource(UnionResource $baseResource, $content): ResourceMarshallerResult|UnionResource
    {
        $dependantResources = $baseResource->getResourceMap();
        $discriminatorKey = $baseResource->getDiscriminatorKey();

        if (!array_key_exists($discriminatorKey, $content)) {
            $path = $this->getCurrentPath($discriminatorKey);
            $this->pushPathError($path, new RequiredValidationError());
            return new ResourceMarshallerResult($baseResource, $this->validationErrors);
        }

        $discriminatorValue = $content[$discriminatorKey];
        if (!is_scalar($discriminatorValue) || !array_key_exists($discriminatorValue, $dependantResources)) {
            $path = $this->getCurrentPath($discriminatorKey);
            $this->pushPathError($path, new UnknownUnionDiscriminatorValidationError(
                array_keys($dependantResources),
                $discriminatorValue,
            ));
            return new ResourceMarshallerResult($baseResource, $this->validationErrors);
        }

        return $dependantResources[$discriminatorValue];
    }

    public function marshalResourceFields(RestingResource $resource, array $content)
    {
        $fields = $this->getFields($resource);
        $resourceContext = new ArrayResourceContext($fields, $content, $this->isStringBased);
        $resource->prepare($resourceContext);

        foreach ($fields as $key => $field) {

            $requiredValidator = $field->getRequiredValidator();
            $nullableValidator = $field->getNullableValidator();
            $forbiddenValidator = $field->getForbiddenValidator();
            $isProvided = array_key_exists($key, $content);
            $field->setFilled($isProvided);

            if (!$isProvided) {

                if ($requiredValidator->hasPredicates() && $requiredValidator->passes($resourceContext)) {
                    $path = $this->getCurrentPath($key);
                    $this->pushPathError($path, new RequiredValidationError());
                    continue;
                }

                if (!$requiredValidator->hasPredicates() && $requiredValidator->isRequired()) {
                    $path = $this->getCurrentPath($key);
                    $this->pushPathError($path, new RequiredValidationError());
                    continue;
                }

                $defaultValue = null;
                foreach ($requiredValidator->getDefaultValues() as $possibleDefault) {
                    if ($possibleDefault->passes($resourceContext)) {
                        $defaultValue = $possibleDefault->getValue();
                        break;
                    }
                }
            } else {

                if ($forbiddenValidator->isForbidden($resourceContext)) {
                    $path = $this->getCurrentPath($key);
                    $this->pushPathError($path, new ForbiddenValidationError());
                    continue;
                }

            }

            $fieldValue = $isProvided ? $content[$key] : $defaultValue;

            if ($fieldValue === null) {
                foreach ($nullableValidator->getDefaultValues() as $possibleDefault) {
                    if ($possibleDefault->passes($resourceContext)) {
                        $fieldValue = $possibleDefault->getValue();
                        break;
                    }
                }
            }

            // the default value has already been validated when it was set
            // we can therefore just set the value and proceed with the other fields
            if (!$isProvided) {
                $field->set($fieldValue);
                $field->setFilled(false);
                continue;
            }

            if ($fieldValue === null) {

                if ($nullableValidator->hasPredicates() && !$nullableValidator->passes($resourceContext)) {
                    $path = $this->getCurrentPath($key);
                    $this->pushPathError($path, new NullableValidationError());
                    continue;
                }

                if (!$nullableValidator->hasPredicates() && !$nullableValidator->isNullable()) {
                    $path = $this->getCurrentPath($key);
                    $this->pushPathError($path, new NullableValidationError());
                    continue;
                }

                $field->set(null);
                continue;
            }

            if ($field instanceof ResourceField) {

                $this->pushPath($key);
                $resource = $field->getReferenceResource();

                if ($resource instanceof UnionResource) {
                    $resource = $this->getUnionSubResource($resource, $fieldValue);
                    if ($resource instanceof ResourceMarshallerResult) {
                        return $resource;
                    }
                }

                $this->marshalResourceFields($resource, $fieldValue);
                $field->set($resource);
                $this->popPath();
                continue;
            }

            if ($field instanceof ResourceArrayField) {
                $this->pushPath($key);
                $this->marshalResourceArrayField($field, $fieldValue);
                $this->popPath();
                continue;
            }

            $parser = $field->getParser();
            $parseContext = new DefaultParseContext($fieldValue, $this->isStringBased);

            if ($parser->shouldParse($parseContext)) {
                if ($parseErrors = $parser->canParse($parseContext)) {
                    foreach ($parseErrors as $parseError) {
                        $parseError->prependPath($this->getCurrentPath($key));
                        $this->pushPathError($parseError->getPath(), $parseError);
                    }

                    continue;
                }

                $fieldValue = $parser->parse($parseContext);
            }

            $exceptionHandler = new ValidationExceptionHandler();

            $errors = [];
            $resolvers = $field->getValidator()->getValidatorResolvers();
            if ($fieldValue !== null) {
                foreach ($resolvers as $resolver) {
                    $validators = $resolver->resolve($resourceContext);
                    foreach ($validators as $validator) {
                        $errors = array_merge($errors, array_map(function (ValidationError $error) use ($key) {
                            return $error->prependPath($key);
                        }, $validator->validate($fieldValue)));
                    }
                }
            }

            if ($errors) {
                foreach ($errors as $error) {
                    $this->pushError($error);
                }

                continue;
            }

            $exceptionHandler->suppress($this->getCurrentPath($key), fn() => $field->set($fieldValue));
            foreach ($exceptionHandler->getErrors() as $validationError) {
                $this->pushError($validationError);
            }
        }

        $resource->finish();

        $resourceValidators = $resource->getResourceValidators();
        foreach ($resourceValidators as $resourceValidator) {
            foreach ($resourceValidator->validate() as $resourceValidationError) {
                $this->pushError($resourceValidationError);
            }
        }
    }

    private function marshalResourceArrayField(ResourceArrayField $field, $values)
    {
        if (!is_array($values) || $this->isAssociativeArray($values)) {
            $path = $this->getCurrentPath();
            $this->pushPathError($path, new NotArrayValidationError($values));
            return;
        }

        $resources = [];
        foreach ($values as $index => $value) {
            $resource = $field->getResourceFactory()();
            if ($resource instanceof UnionResource) {
                $subResource = $this->getUnionSubResource($resource, $value);
                if ($subResource instanceof ResourceMarshallerResult) {
                    return $resource;
                }

                $resource = $subResource;
            }

            $resources[] = $resource;
            $this->pushPath($index);
            $this->marshalResourceFields($resource, $value);
            $this->popPath();
        }

        $field->set($resources);
    }

    public function marshalResources(ResourceFactory $factory, $content): ResourceMarshallerResult
    {
        $this->reset();

        $hydrated = [];

        if (!is_array($content) || $this->isAssociativeArray($content)) {
            $this->pushRootError(new NotArrayValidationError($content));
            return new ResourceMarshallerResult($hydrated, $this->validationErrors);
        }

        foreach ($content as $key => $value) {

            $this->pushPath($key);
            $resource = $factory->create();

            if (!is_array($value)) {
                $path = $this->getCurrentPath();
                $this->pushPathError($path, new NotArrayValidationError($value));
                continue;
            }

            if ($resource instanceof UnionResource) {
                $resource = $this->getUnionSubResource($resource, $value);
                if ($resource instanceof ResourceMarshallerResult) {
                    return $resource;
                }
            }

            $this->marshalResourceFields($resource, $value);
            $hydrated[] = $resource;
            $this->popPath();
        }

        return new ResourceMarshallerResult($hydrated, $this->validationErrors);
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    private function getFields(RestingResource $resource): array
    {
        return $resource->fields()->toArray();
    }

    private function getCurrentPath(string ...$segments): string
    {
        return join('.', array_merge($this->currentPath, $segments));
    }

    private function isAssociativeArray(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}