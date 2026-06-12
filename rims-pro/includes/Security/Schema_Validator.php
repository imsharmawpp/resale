<?php
declare(strict_types=1);

namespace RimsPro\Security;

use RimsPro\Domain\Validation_Result;

/**
 * Schema-driven validator. Returns Validation_Result with field-keyed messages.
 *
 * Supported rules per field: required, type (string|int|float|bool|email|mobile|enum),
 * max (length for strings, value for ints/floats), min, in (list), regex.
 */
final class Schema_Validator {

    /**
     * @param array<string, array<string, mixed>> $schema
     * @param array<string, mixed>                $input
     */
    public function validate( array $schema, array $input ): Validation_Result {
        $errors = [];
        foreach ( $schema as $field => $rules ) {
            $value = $input[ $field ] ?? null;

            if ( ! empty( $rules['required'] ) ) {
                if ( $value === null || $value === '' ) {
                    $errors[ $field ][] = sprintf( '%s is required.', (string) $field );
                    continue;
                }
            } elseif ( $value === null || $value === '' ) {
                continue;
            }

            // Type checks
            $type = $rules['type'] ?? null;
            switch ( $type ) {
                case 'string':
                    if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
                        $errors[ $field ][] = sprintf( '%s must be a string.', (string) $field );
                    }
                    break;
                case 'int':
                    if ( ! is_int( $value ) && ! ( is_string( $value ) && ctype_digit( ltrim( $value, '-' ) ) ) ) {
                        $errors[ $field ][] = sprintf( '%s must be an integer.', (string) $field );
                    }
                    break;
                case 'float':
                    if ( ! is_numeric( $value ) ) {
                        $errors[ $field ][] = sprintf( '%s must be a number.', (string) $field );
                    }
                    break;
                case 'bool':
                    if ( ! is_bool( $value ) && $value !== 0 && $value !== 1 && $value !== '0' && $value !== '1' ) {
                        $errors[ $field ][] = sprintf( '%s must be true/false.', (string) $field );
                    }
                    break;
                case 'email':
                    if ( ! filter_var( (string) $value, FILTER_VALIDATE_EMAIL ) ) {
                        $errors[ $field ][] = sprintf( '%s must be a valid email.', (string) $field );
                    }
                    break;
                case 'mobile':
                    $digits = preg_replace( '/[^0-9]/', '', (string) $value );
                    if ( strlen( (string) $digits ) < 7 || strlen( (string) $digits ) > 15 ) {
                        $errors[ $field ][] = sprintf( '%s must be a valid mobile number.', (string) $field );
                    }
                    break;
                case 'enum':
                    $allowed = $rules['in'] ?? [];
                    if ( ! in_array( $value, (array) $allowed, true ) ) {
                        $errors[ $field ][] = sprintf( '%s must be one of: %s', (string) $field, implode( ', ', (array) $allowed ) );
                    }
                    break;
            }

            // Length / range checks.
            if ( isset( $rules['max'] ) ) {
                if ( is_string( $value ) ) {
                    if ( mb_strlen( $value ) > (int) $rules['max'] ) {
                        $errors[ $field ][] = sprintf( '%s exceeds maximum length.', (string) $field );
                    }
                } elseif ( is_numeric( $value ) ) {
                    if ( (float) $value > (float) $rules['max'] ) {
                        $errors[ $field ][] = sprintf( '%s exceeds maximum value.', (string) $field );
                    }
                }
            }
            if ( isset( $rules['min'] ) ) {
                if ( is_string( $value ) ) {
                    if ( mb_strlen( $value ) < (int) $rules['min'] ) {
                        $errors[ $field ][] = sprintf( '%s below minimum length.', (string) $field );
                    }
                } elseif ( is_numeric( $value ) ) {
                    if ( (float) $value < (float) $rules['min'] ) {
                        $errors[ $field ][] = sprintf( '%s below minimum value.', (string) $field );
                    }
                }
            }
            if ( isset( $rules['regex'] ) && is_string( $value ) ) {
                if ( ! preg_match( (string) $rules['regex'], $value ) ) {
                    $errors[ $field ][] = sprintf( '%s has invalid format.', (string) $field );
                }
            }
        }
        return $errors ? Validation_Result::fail( $errors ) : Validation_Result::ok();
    }
}
