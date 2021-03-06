<?php
namespace Nethgui\Widget\Xhtml;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
 * 
 * This script is part of NethServer.
 * 
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *
 */
class ObjectPicker extends \Nethgui\Widget\XhtmlWidget
{

    private $metadata;
    private $values = array();

    private function initializeRendering()
    {
        $this->metadata = array(
            'value' => $this->getAttribute('objectValue', 0),
            'label' => $this->getAttribute('objectLabel', $this->getAttribute('objectValue', 0)),
            'url' => $this->getAttribute('objectUrl', FALSE),
            'listenToEvents' => array(),
            'selector' => FALSE,
        );

        $name = $this->getAttribute('name', FALSE);
        if ( ! empty($name)) {
            $this->insert($this->view->checkBox($name, '', 0));
            $this->metadata['selector'] = $name;
        }

        foreach ($this->getChildren() as $child) {
            $childName = $child->getAttribute('name');

            if ($this->view[$childName] instanceof \Traversable) {
                $value = iterator_to_array($this->view[$childName]);
            } elseif (is_array($this->view[$childName])) {
                $value = $this->view[$childName];
            } elseif (empty($this->view[$childName])) {
                $value = array();
            } else {
                throw new \UnexpectedValueException(sprintf('%s: Invalid value type in view key `%s`: %s. Must be one of Traversable, array or an empty value.', get_class($this), $childName, var_export($this->view[$childName], TRUE)), 1322149475);
            }

            $this->values[$childName] = $value;
            $this->metadata['listenToEvents'][] = $this->view->getClientEventTarget($childName);
        }
    }

    public function insert(\Nethgui\Renderer\WidgetInterface $child)
    {
        if ( ! $child instanceof CheckBox) {
            throw new \InvalidArgumentException(sprintf('%s: Unsupported widget class: %s', get_class($this), get_class($child)), 1322149476);
        }

        if ( ! $child->hasAttribute('uncheckedValue')) {
            $child->setAttribute('uncheckedValue', FALSE);
        }

        // Force help id to FALSE (disabled) - No help context can be specified here.
        $child->setAttribute('helpId', FALSE);

        $childFlags = $child->getAttribute('flags', 0);

        // Mask LABEL_* flags:
        $childFlags &= ~ (\Nethgui\Renderer\WidgetFactoryInterface::LABEL_ABOVE | \Nethgui\Renderer\WidgetFactoryInterface::LABEL_RIGHT | \Nethgui\Renderer\WidgetFactoryInterface::LABEL_LEFT);

        // Force to STATE_DISABLED & LABEL_RIGHT
        $childFlags |= \Nethgui\Renderer\WidgetFactoryInterface::LABEL_RIGHT | \Nethgui\Renderer\WidgetFactoryInterface::STATE_DISABLED;

        // Fix the flags:
        $child->setAttribute('flags', $childFlags);

        return parent::insert($child);
    }

    protected function renderContent()
    {
        $this->initializeRendering();

        $content = '';
        $content .= $this->openTag('div', array('class' => 'ObjectPicker ' . implode(' ', $this->metadata['listenToEvents'])));

        // render the an hidden input, as fallback empty post value:
        foreach ($this->getChildren() as $child) {
            $content .= $this->selfClosingTag('input', array('type' => 'hidden', 'value' => '', 'name' => $this->getControlName($child->getAttribute('name'))));
        }

        $content .= $this->renderMeta();
        $content .= $this->renderObjects();

        $content .= $this->closeTag('div');

        if ($this->hasAttribute('template')) {
            $fieldsetWidget = new Fieldset($this->view);
            $fieldsetWidget
                ->setAttribute('template', $this->getAttribute('template'))
                ->setAttribute('icon-before', $this->getAttribute('icon-before'))
                ->insert($this->view->literal($content))
            ;

            return $fieldsetWidget;
        }

        return $content;
    }

    private function renderMeta()
    {
        $content = '';

        // meta
        $content .= $this->selfClosingTag('input', array('name' => $this->getControlName('meta'), 'type' => 'hidden', 'disabled' => 'disabled', 'value' => json_encode($this->metadata), 'class' => 'metadata'));

        // searchbox
        $content .= $this->openTag('div', array('class' => 'searchbox'));
        $content .= $this->selfClosingTag('input', array('type' => 'text', 'class' => 'TextInput', 'disabled' => 'disabled', 'value' => '', 'placeholder' => $this->view->translate('Search...')));
        $content .= ' ' . $this->openTag('button', array('type' => 'button', 'class' => 'Button custom', 'disabled' => 'disabled')) . htmlspecialchars($this->view->translate('Add')) . $this->closeTag('button');
        $content .= $this->closeTag('div');

        // schema
        $content .= $this->openTag('div', array('class' => 'schema'));
        $content .= $this->renderChildren();
        $content .= $this->closeTag('div');

        $flags = $this->getAttribute('flags', 0);

        if ( ! ($flags & \Nethgui\Renderer\WidgetFactoryInterface::STATE_UNOBSTRUSIVE)) {
            $content = $this->escapeUnobstrusive($content);
        }

        return $content;
    }

    private function renderObjects()
    {
        $objects = $this->getAttribute('objects', array());
        $flags = $this->getAttribute('flags', 0);

        $attributes = array();

        if (is_string($objects)) {
            $attributes['class'] = 'Objects ' . $this->view->getClientEventTarget($objects);
            $attributes['id'] = $this->view->getUniqueId($objects);
            $objects = $this->view[$objects];
        } else {
            $attributes['class'] = 'Objects ' . $this->view->getClientEventTarget('Datasource');
            $attributes['id'] = $this->view->getUniqueId('Datasource');
        }

        $content = '';

        if ( ! ($flags & \Nethgui\Renderer\WidgetFactoryInterface::STATE_UNOBSTRUSIVE)) {
            if ((is_array($objects) || $objects instanceof \Countable) && count($objects) > 0) {
                $content .= '<ul>';

                foreach ($objects as $index => $object) {
                    $content .= '<li>';
                    $content .= $this->renderObjectWidget($index, $object);
                    $content .= '</li>';
                }

                $content .= '</ul>';
            }
        }

        return $this->openTag('div', $attributes) . $content . $this->closeTag('div');
    }

    private function renderObjectWidget($index, $object)
    {
        $flags = $this->getAttribute('flags', 0);

        $content = '';

        $contentSelectionFragment = '';
        $contentProperties = '';

        if ($this->metadata['url'] && isset($object[$this->metadata['url']])) {
            $content .= $this->openTag('a', array('class' => 'label', 'href' => $object[$this->metadata['url']])) . htmlspecialchars($object[$this->metadata['label']]) . $this->closeTag('a');
        } else {
            $content .= $this->openTag('span', array('class' => 'label')) . htmlspecialchars($object[$this->metadata['label']]) . $this->closeTag('span');
        }

        $content .= '<div class="checkboxset">';
        foreach ($this->getChildren() as $child) {
            $childClone = clone $child;

            $childFlags = $child->getAttribute('flags', 0);

            // Mask STATE_DISABLED
            $childFlags &= ~\Nethgui\Renderer\WidgetFactoryInterface::STATE_DISABLED;

            if (in_array($object[$this->metadata['value']], $this->values[$child->getAttribute('name')])) {
                $childFlags |= \Nethgui\Renderer\WidgetFactoryInterface::STATE_CHECKED;
            } else {
                $childFlags &= ~\Nethgui\Renderer\WidgetFactoryInterface::STATE_CHECKED;
            }

            $childClone->setAttribute('flags', $childFlags);
            $childClone->setAttribute('name', $child->getAttribute('name') . '/' . $index);
            $childClone->setAttribute('label', $child->getAttribute('label', $this->getTranslateClosure($child->getAttribute('name') . '_label')));
            $childClone->setAttribute('value', $object[$this->metadata['value']]);

            $content .= $childClone->renderContent();
        }
        $content .= '</div>';

        return $content;
    }

}

