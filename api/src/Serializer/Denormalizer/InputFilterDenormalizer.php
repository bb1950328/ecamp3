<?php

namespace App\Serializer\Denormalizer;

use App\InputFilter\FilterAttribute;
use App\InputFilter\InputFilter;
use App\InputFilter\UnexpectedValueException;
use Generator;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;

/**
 * This class is responsible for reading and calling any InputFilter Attributes on API entities.
 * InputFilters can be used to post-process the payload data submitted in write requests.
 */
class InputFilterDenormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface {
    use DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'INPUT_FILTER_DENORMALIZER_ALREADY_CALLED';

    private ServiceLocator $inputFilterLocator;

    public function __construct(ServiceLocator $inputFilterLocator) {
        $this->inputFilterLocator = $inputFilterLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $type, $format = null, array $context = []) {
        $data = $this->filterInputs($data, $type);

        $context[self::ALREADY_CALLED] = true;

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []) {
        // Make sure we don't run this denormalizer twice.
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return is_array($data);
    }

    /**
     * Iterates over all properties of the entity class and applies any filter attributes.
     *
     * Implementation based on Symfony/Component/Validator/Mapping/Loader/AnnotationLoader.php
     */
    protected function filterInputs($data, string $className): array {
        if (!is_array($data)) {
            throw new UnexpectedValueException($data);
        }

        $reflClass = $this->getReflectionClass($className);
        $filterAttributes = [];
        foreach ($reflClass->getProperties() as $property) {
            if ($property->getDeclaringClass()->name === $className) {
                foreach ($this->getInputFilterAttributes($property) as $filterAttribute) {
                    $filterAttributes[] = [$property->name, $filterAttribute];
                }
            }
        }

        usort($filterAttributes, function ($a, $b) {
            // Comparing B to A ensures that priorities are sorted in descending order,
            // as opposed to comparing A to B
            return $b[1]->getPriority() <=> $a[1]->getPriority();
        });

        foreach ($filterAttributes as $tuple) {
            $filterAttribute = $tuple[1];
            if ($filterAttribute instanceof FilterAttribute) {
                $data = $this->applyFilter($data, $tuple[0], $filterAttribute);
            }
        }

        return $data;
    }

    protected function getInputFilterAttributes(object $reflection): Generator {
        foreach ($reflection->getAttributes(FilterAttribute::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            yield $attribute->newInstance();
        }
    }

    protected function getReflectionClass($className): ReflectionClass {
        return new ReflectionClass($className);
    }

    protected function applyFilter($data, string $propertyName, FilterAttribute $filterAttribute): array {
        /** @var InputFilter $filter */
        $filter = $this->inputFilterLocator->get($filterAttribute->filteredBy());
        $filter->setFilterAttribute($filterAttribute);

        return $filter->applyTo($data, $propertyName);
    }
}
