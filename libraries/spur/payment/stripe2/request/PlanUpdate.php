<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2\request;

use df;
use df\core;
use df\spur;
use df\mint;

class PlanUpdate implements spur\payment\stripe2\IPlanUpdateRequest {

    use TRequest_Metadata;
    use TRequest_Plan;
    use TRequest_StatementDescriptor;
    use TRequest_TrialDays;

/*
    plan
    ?metadata
    ?name
    ?statement_descriptor
    ?trial_period_days
*/

    protected $_name;

    public function __construct(string $id) {
        $this->setPlanId($id);
    }


    public function setName(/*?string*/ $name) {
        $this->_name = $name;
        return $this;
    }

    public function getName()/*: string*/ {
        return $this->_name;
    }


    public function toArray(): array {
        $output = [];

        if($this->_name !== null) {
            $output['name'] = $this->_name;
        }

        $this->_applyMetadata($output);
        $this->_applyStatementDescriptor($output);
        $this->_applyTrialDays($output);

        return $output;
    }
}