<?php

namespace PropTypes;

class PropTypes {
  private static $types = [];

  // TODO: Use construct union type (PHP 8.0): string|array
  public static function register($name, $__2) {
    if (is_string($name)) {
      $name = trim($name);

      if (!$name) {
        throw new \Exception("Type name must be a string with length more than 0", 500);
      }

      if (PROP_TYPES_WARNINGS_ENABLED && isset(static::$types[$name])) {
        trigger_error("Overriding already defined `{$name}` type", E_USER_NOTICE);
      }

      if ($__2 instanceof TypeInterface) {
        if ($__2->isNullable()) {
          throw new \Exception("Registering a NullableType is prohibited", 500);
        }

        static::$types[$name] = new TypeAlias($name, $__2);
      } elseif ($__2 instanceof \Closure) {
        if ((new \ReflectionFunction($__2))->getNumberOfParameters() === 0) {
          static::$types[$name] = new Type($name, $__2());
        } else {
          static::$types[$name] = $__2;
        }
      } else {
        throw new \Exception("Expecting one of [Type, Type factory closure, validation closure factory closure].", 500);
      }

      return static::$types[$name];
    } if (is_array($name)) {
      foreach ($name as $member) {
        static::register($member, $__2);
      }
    } else {
      throw new \Exception("Expecting string or array", 500);
    }
  }

  public static function __callStatic($name = '', array $arguments = []): TypeInterface {
    if (!isset(static::$types[$name])) {
      throw new \Exception("`{$name}` PropType is not registered", 404);
    }

    if (static::$types[$name] instanceof TypeInterface) {
      if (count($arguments) > 0) {
        throw new \Exception("`{$name}` type creation does not expect any arguments", 500);
      }

      return static::$types[$name];
    }

    $validation = call_user_func_array(static::$types[$name], $arguments);

    if (is_callable($validation)) {
      return new Type($name, $validation);
    }

    if ($validation instanceof TypeInterface) {
      return $validation;
    }

    throw new \Exception("Expecting factory to return validation closure of instance of TypeInterface", 500);
  }
}
