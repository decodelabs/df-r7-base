<?php
/**
 * This file is part of the PHPStanDecodeLabs package
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);
namespace DecodeLabs\PHPStan\Plug;

use df\core\IContextAware;
use df\arch\Context;

use DecodeLabs\PHPStan\MethodReflection;
use DecodeLabs\PHPStan\StaticMethodReflection;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\Native\NativeParameterReflection as ParameterReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection as MethodReflectionInterface;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Generic\TemplateTypeMap;

class InvokeReflectionExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    /**
     * @var \PHPStan\Broker\Broker
     */
    protected $broker;

    /**
     * @param \PHPStan\Broker\Broker $broker
     */
    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * Returns the current broker.
     *
     * @return \PHPStan\Broker\Broker
     */
    public function getBroker(): Broker
    {
        return $this->broker;
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (!is_a($classReflection->getName(), IContextAware::class, true)) {
            return false;
        }

        if ($this->getBroker()->getClass(Context::class)->hasMethod($methodName)) {
            return true;
        }

        $plugClass = 'df\\plug\\'.ucfirst($methodName);
        return class_exists($plugClass);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflectionInterface
    {
        $context = $this->getBroker()->getClass(Context::class);

        if ($context->hasMethod($methodName)) {
            return $context->getMethod($methodName, new OutOfClassScope());
        }

        return (new MethodReflection($classReflection, $methodName, $this->getWidgetVariants()))
            ->setPrivate(false);
    }

    protected function getWidgetVariants()
    {
        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                null,
                [
                    new ParameterReflection('a', true, TypeCombinator::addNull(new MixedType()), PassedByReference::createNo(), false, null),
                    new ParameterReflection('b', true, TypeCombinator::addNull(new MixedType()), PassedByReference::createNo(), false, null),
                    new ParameterReflection('c', true, TypeCombinator::addNull(new MixedType()), PassedByReference::createNo(), false, null),
                    new ParameterReflection('d', true, TypeCombinator::addNull(new MixedType()), PassedByReference::createNo(), false, null)
                ],
                false,
                new MixedType()
            )
        ];
    }
}
