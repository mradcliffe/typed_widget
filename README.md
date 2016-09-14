# Typed Widget

Typed Widget provides a service to dynamically create form elements based on [Typed Data](https://www.drupal.org/node/1794140) data types.

[![Build Status](https://travis-ci.org/mradcliffe/typed_widget.svg?branch=8.x-1.x)](https://travis-ci.org/mradcliffe/typed_widget)

## Usage

1. Get a form element for a primitive data type such as a ISO 8601 date.

```php
$formBuilder = \Drupal::service('typed_widget.element_builder');
$form['date'] = $formBuilder->getElementFor('datetime_iso8601');
```

2. UNSTABLE: Get form elements for an entity type or one form element for a property on an entity type.

This functionality is **unstable** and the return value may change. Currently the typed element builder will look up an entity's form handler and use it if it exists. Otherwise the entity type will be treated as a complex data type.

```php
$form = $formBuilder->getElementFor('entity:user');
unset($form['#process']);
$mailElement = $formBuilder->getElementFor('entity:user', 'mail');
```

3. Get only form elements that are only required. By default, all properties that are not read-only or computed will have a form element.

```php
$formBuilder->setNonRequiredProperties(FALSE);
$form = $formBuilder->getElementFor('xero_bank_transaction');
```

4. Include read-only properties, which appear as disabled form elements. The `nonRequiredProperties` property must also be set to `TRUE` (default).

```php
$formBuilder->setReadOnlyProperties(TRUE);
$form = $formBuilder->getElementFor('xero_user');
```

5. Get form element required for field property definitions without the context of an entity or form display.

```php
$form['phone'] = $formBuilder->getElementFor('field_item:telephone');
```

6. UNSTABLE: Get form elements for an Article node.

This functionality is **unstable** and the API may change with regard to the way in which the bundle is set or the return output (see above).

```php
$form = $formBuilder->getElementFor('entity:node', NULL, ['type' => 'article']);
$form = $formBuilder->getElementFor('entity:node', 'field_favorite_color', ['type' => 'article']);
```

## TODO

* [ ] Make a stable and usable API for entity types forms.
	* Option to use complex data or form handler.
	* Option to set required options.
	* Split element builder for entity types into a separate class like primitive element builder.
* [ ] Add an interface that non-entity complex data can use to implement:
	* [ ] custom form handler
	* [ ] custom view handler
* [ ] Support typed config?
* [ ] Support add more functionality for list elements.
