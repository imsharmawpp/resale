<?php
declare(strict_types=1);

namespace RimsPro\Services;

use RimsPro\Domain\Lead;
use RimsPro\Domain\Validation_Result;
use RimsPro\Repositories\Lead_Repository;
use RimsPro\Security\Schema_Validator;

/**
 * Validates + persists Lead. Property 18 / Req 15.6, 15.7, 16.2.
 * Valid input -> persist with capture source in `new` stage.
 * Missing/malformed name or mobile -> reject with field-level message,
 * persist nothing.
 */
final class Lead_Capture {

    public const SCHEMA = [
        'name'         => [ 'required' => true, 'type' => 'string', 'max' => 191, 'min' => 1 ],
        'mobile'       => [ 'required' => true, 'type' => 'mobile' ],
        'email'        => [ 'required' => false, 'type' => 'email' ],
        'requirements' => [ 'required' => false, 'type' => 'string', 'max' => 5000 ],
        'source'       => [ 'required' => false, 'type' => 'string', 'max' => 60 ],
        'unit_id'      => [ 'required' => false, 'type' => 'int' ],
    ];

    public function __construct(
        private Lead_Repository $repo,
        private Schema_Validator $validator,
    ) {
    }

    /**
     * @return array{result:Validation_Result, lead_id?:int}
     */
    public function submit( int $tenant_id, array $input ): array {
        $check = $this->validator->validate( self::SCHEMA, $input );
        if ( ! $check->isOk() ) {
            return [ 'result' => $check ];
        }
        $lead                 = new Lead();
        $lead->tenant_id      = $tenant_id;
        $lead->name           = trim( (string) $input['name'] );
        $lead->mobile         = trim( (string) $input['mobile'] );
        $lead->email          = isset( $input['email'] ) ? trim( (string) $input['email'] ) : null;
        $lead->requirements   = isset( $input['requirements'] ) ? (string) $input['requirements'] : null;
        $lead->source         = (string) ( $input['source'] ?? 'web' );
        $lead->unit_id        = isset( $input['unit_id'] ) ? (int) $input['unit_id'] : null;
        $lead->pipeline_stage = 'new';
        $id                   = $this->repo->persist( $tenant_id, $lead );
        return [ 'result' => Validation_Result::ok(), 'lead_id' => $id ];
    }

    public function validate( array $input ): Validation_Result {
        return $this->validator->validate( self::SCHEMA, $input );
    }
}
