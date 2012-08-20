<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\template;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;
    
abstract class SearchSelectorDelegate extends SelectorDelegateBase {

	public function renderFieldSet($fieldSetName) {
		$fs = $this->html->fieldSet($fieldSetName);

		// Search
        $fs->addFieldArea($this->_('Search'))->push(
            $this->html->textbox(
                    $this->fieldName('search'), 
                    $this->values->search
                ),

            $this->html->eventButton(
                    $this->eventName('search'), 
                    $this->_('Search')
                )
                ->shouldValidate(false)
                ->setIcon('search')
        );



        // Show search results
        if(!$this->values->searchResults->isEmpty()) {
        	$searchResults = $this->_fetchResultList($this->values->searchResults->toArray());
        	$searchResults = $this->_normalizeQueryResult($searchResults);

        	if($this->_isQueryResultEmpty($searchResults)) {
        		unset($this->values->searchResults);
        	} else {
        		$fa = $fs->addFieldArea($this->_('Search results'));

        		foreach($searchResults as $result) {
        			$id = $this->_getResultId($result);
        			$name = $this->_getResultDisplayName($result);

        			$fa->push(
        				$this->html->hidden($this->fieldName('searchResults[]'), $id),
        				$this->html->checkbox(
                                $this->fieldName('selected['.$id.']'),
                                $this->values->selected->has($id),
                                $name,
                                $id
                            ),
                        $this->html->string('<br />')
    				);
        		}

        		$fa->addEventButton(
                        $this->eventName('select'),
                        $this->_('Add selected')
                    )
                    ->shouldValidate(false)
                    ->setIcon('add');
        	}
        }


        // Show selected
        if(!$this->values->selected->isEmpty()) {
        	$selectedList = $this->_fetchResultList($this->values->selected->getKeys());
        	$selectedList = $this->_normalizeQueryResult($selectedList);

        	if($this->_isQueryResultEmpty($selectedList)) {
        		unset($this->values->selected);
        	} else {
        		$fa = $fs->addFieldArea($this->_('Selected'));

        		foreach($selectedList as $result) {
        			$id = $this->_getResultId($result);
        			$name = $this->_getResultDisplayName($result);

        			$fa->push(
        				$this->html->hidden($this->fieldName('selected['.$id.']'), $id),

                        $name,

                        $this->html->eventButton(
                                $this->eventName('remove', $id), 
                                $this->_('Remove')
                            )
                            ->shouldValidate(false)
                            ->setIcon('remove'),

                        $this->html->string('<br />')
    				);
        		}
        	}
        }

        return $fs;
	}	

	abstract protected function _fetchResultList(array $ids);

	protected function _getResultId($result) {
        return $result['id'];
    }

    abstract protected function _getResultDisplayName($result);

    protected function _onSearchEvent() {
    	unset($this->values->searchResults);

    	$search = $this->data->newValidator()
            ->addField('search', 'text')
                ->setSanitizer(function($value) {
                    if(empty($value)) {
                        $value = '*';
                    }

                    return $value;
                })
                ->end()
            ->validate($this->values)
            ->getValue('search');


        if($this->values->search->isValid()) {
        	$this->values->searchResults = $this->_getSearchResultIdList($search, $this->values->selected->getKeys());
        }
    }

    abstract protected function _getSearchResultIdList($search, array $selected);

    protected function _onSelectEvent() {
    	unset($this->values->search, $this->values->searchResults);
    }

    protected function _onRemoveEvent($id) {
    	unset($this->values->selected->{$id});
    }


    public function setSelected(array $ids) {
    	foreach($ids as $id) {
    		$this->values->selected[$id] = $id;
    	}

    	return $this;
    }

    public function getSelected() {
    	return $this->values->selected->toArray();
    }

    public function apply() {
    	if($this->_isRequired) {
    		if($this->values->selected->isEmpty()) {
    			$this->values->search->addError('required', $this->_(
    				'You must selected at least one entry'
				));
    		}
    	}

    	return $this->getSelected();
    }
}