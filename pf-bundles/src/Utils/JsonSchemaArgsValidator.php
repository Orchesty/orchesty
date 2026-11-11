<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Utils;

/**
 * Class JsonSchemaArgsValidator
 *
 * Minimal JSON-Schema-ish validator for flat argument objects. Shared by the
 * MCP tool argument check (enterprise) and the Question user-task answer
 * check: even if an LLM emits missing/extra/mistyped arguments, nothing is
 * started — a human-readable error is returned instead.
 *
 * Supported keywords: type, required, properties, additionalProperties, enum, minimum, maximum, pattern.
 * Anything else in the schema is ignored (best-effort, not a full validator).
 *
 * @package Hanaboso\PipesFramework\Utils
 */
final class JsonSchemaArgsValidator
{

    private const string PATTERN_DELIMITER = "\x01";

    /**
     * Coerces top-level scalar args to the types declared in the schema before
     * validation. LLMs (and JSON envelopes) routinely emit numbers/booleans as
     * strings — e.g. {"degrees":"20"} for an `integer` property — which the
     * strict type check below would otherwise reject with a 500. We only widen
     * lossless, unambiguous scalar conversions; arrays/objects and unknown
     * properties are passed through untouched.
     *
     * @param mixed[] $schema
     * @param mixed[] $args
     *
     * @return mixed[]
     */
    public static function coerce(array $schema, array $args): array
    {
        $properties = is_array($schema['properties'] ?? NULL) ? $schema['properties'] : [];

        if ($properties === []) {
            return $args;
        }

        foreach ($args as $key => $value) {
            if (!isset($properties[$key]) || !is_array($properties[$key])) {
                continue;
            }

            $type = $properties[$key]['type'] ?? NULL;

            if (!is_string($type) || $type === '') {
                continue;
            }

            $args[$key] = self::coerceScalar($type, $value);
        }

        return $args;
    }

    /**
     * Returns the first validation error message, or NULL when the args are
     * valid against the schema.
     *
     * @param mixed[] $schema
     * @param mixed[] $args
     *
     * @return string|null
     */
    public static function validate(array $schema, array $args): ?string
    {
        return self::errors($schema, $args)[0] ?? NULL;
    }

    /**
     * Returns every validation error message (empty when valid).
     *
     * @param mixed[] $schema
     * @param mixed[] $args
     *
     * @return string[]
     */
    public static function errors(array $schema, array $args): array
    {
        if ($schema === []) {
            return [];
        }

        $errors     = [];
        $properties = is_array($schema['properties'] ?? NULL) ? $schema['properties'] : [];
        $required   = is_array($schema['required'] ?? NULL) ? $schema['required'] : [];

        foreach ($required as $name) {
            if (!is_string($name)) {
                continue;
            }

            if (!array_key_exists($name, $args) || $args[$name] === NULL || $args[$name] === '') {
                $errors[] = sprintf('Missing required parameter "%s".', $name);
            }
        }

        $additionalAllowed = ($schema['additionalProperties'] ?? TRUE) !== FALSE;

        foreach ($args as $key => $value) {
            if (!isset($properties[$key])) {
                if (!$additionalAllowed) {
                    $errors[] = sprintf('Unknown parameter "%s".', (string) $key);
                }

                continue;
            }

            $propSchema = is_array($properties[$key]) ? $properties[$key] : [];

            $typeError = self::checkType((string) $key, $propSchema, $value);
            if ($typeError !== NULL) {
                $errors[] = $typeError;

                continue;
            }

            if (isset($propSchema['enum'])
                && is_array($propSchema['enum'])
                && !in_array($value, $propSchema['enum'], TRUE)
            ) {
                $errors[] = sprintf(
                    'Parameter "%s" must be one of: %s.',
                    (string) $key,
                    implode(
                        ', ',
                        array_map(static fn($v): string => is_scalar($v) ? (string) $v : '?', $propSchema['enum']),
                    ),
                );
            }

            $boundsError = self::checkBounds((string) $key, $propSchema, $value);
            if ($boundsError !== NULL) {
                $errors[] = $boundsError;
            }
        }

        return $errors;
    }

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return mixed
     */
    private static function coerceScalar(string $type, mixed $value): mixed
    {
        switch ($type) {
            case 'integer':
                if (is_int($value)) {
                    return $value;
                }
                if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
                    return (int) trim($value);
                }
                if (is_float($value) && floor($value) === $value) {
                    return (int) $value;
                }

                return $value;
            case 'number':
                if (is_int($value) || is_float($value)) {
                    return $value;
                }
                if (is_string($value) && is_numeric(trim($value))) {
                    $trimmed = trim($value);

                    return preg_match('/^-?\d+$/', $trimmed) === 1 ? (int) $trimmed : (float) $trimmed;
                }

                return $value;
            case 'boolean':
                if (is_bool($value)) {
                    return $value;
                }
                if (is_string($value)) {
                    $normalized = strtolower(trim($value));
                    if (in_array($normalized, ['true', '1'], TRUE)) {
                        return TRUE;
                    }
                    if (in_array($normalized, ['false', '0'], TRUE)) {
                        return FALSE;
                    }
                }
                if (is_int($value) && ($value === 0 || $value === 1)) {
                    return $value === 1;
                }

                return $value;
            case 'string':
                if (is_int($value) || is_float($value)) {
                    return (string) $value;
                }
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return $value;
            default:
                return $value;
        }
    }

    /**
     * @param string  $key
     * @param mixed[] $propSchema
     * @param mixed   $value
     *
     * @return string|null
     */
    private static function checkType(string $key, array $propSchema, mixed $value): ?string
    {
        $type = $propSchema['type'] ?? NULL;

        if (!is_string($type) || $type === '') {
            return NULL;
        }

        $ok = match ($type) {
            'string'          => is_string($value),
            'number'          => is_int($value) || is_float($value),
            'integer'         => is_int($value),
            'boolean'         => is_bool($value),
            'array'           => is_array($value) && array_is_list($value),
            'object'          => is_array($value) && !array_is_list($value),
            default           => TRUE,
        };

        if (!$ok) {
            return sprintf('Parameter "%s" must be of type %s.', $key, $type);
        }

        return NULL;
    }

    /**
     * @param string  $key
     * @param mixed[] $propSchema
     * @param mixed   $value
     *
     * @return string|null
     */
    private static function checkBounds(string $key, array $propSchema, mixed $value): ?string
    {
        $minimum = $propSchema['minimum'] ?? NULL;
        $maximum = $propSchema['maximum'] ?? NULL;
        $pattern = $propSchema['pattern'] ?? NULL;

        if (is_int($value) || is_float($value)) {
            if ((is_int($minimum) || is_float($minimum)) && $value < $minimum) {
                return sprintf('Parameter "%s" must be at least %s.', $key, $minimum);
            }
            if ((is_int($maximum) || is_float($maximum)) && $value > $maximum) {
                return sprintf('Parameter "%s" must be at most %s.', $key, $maximum);
            }
        }

        if (is_string($value) && is_string($pattern) && $pattern !== '' && !self::matchesPattern($pattern, $value)) {
            return sprintf('Parameter "%s" must match the pattern %s.', $key, $pattern);
        }

        return NULL;
    }

    /**
     * An invalid pattern is ignored, like any other keyword this validator doesn't understand.
     *
     * @param string $pattern
     * @param string $value
     *
     * @return bool
     */
    private static function matchesPattern(string $pattern, string $value): bool
    {
        set_error_handler(static fn(): bool => TRUE);

        try {
            $result = preg_match(
                sprintf('%s%s%su', self::PATTERN_DELIMITER, $pattern, self::PATTERN_DELIMITER),
                $value,
            );
        } finally {
            restore_error_handler();
        }

        return $result !== 0;
    }

}
