<?php

namespace Framelix\Framelix\Form;

use Framelix\Framelix\Form\Field\File;
use Framelix\Framelix\Html\ColorName;
use Framelix\Framelix\Html\HtmlAttributes;
use Framelix\Framelix\Lang;
use Framelix\Framelix\Network\Request;
use Framelix\Framelix\Network\Response;
use Framelix\Framelix\Network\UploadedFile;
use Framelix\Framelix\Storable\Storable;
use Framelix\Framelix\Storable\StorableFile;
use Framelix\Framelix\Url;
use Framelix\Framelix\Utils\ArrayUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\RandomGenerator;
use Framelix\Framelix\View;
use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;

use function array_shift;
use function get_class;
use function is_array;
use function ob_end_clean;
use function ob_get_contents;
use function ob_start;

/**
 * Framelix form generator
 */
class Form implements JsonSerializable
{
    /**
     * The id for the form
     * @var string
     */
    public string $id;

    /**
     * The label/title above the form if desired
     * @var string|null
     */
    public ?string $label;

    /**
     * Additional form html attributes
     * @var HtmlAttributes|null
     */
    public ?HtmlAttributes $htmlAttributes = null;

    /**
     * The fields attached to this form
     * @var Field[]
     */
    public array $fields = [];

    /**
     * The buttons attached to the form
     * @var array
     */
    public array $buttons = [];

    /**
     * Submit method
     * post or get
     * @var string
     */
    public string $submitMethod = 'post';

    /**
     * The url to submit to
     * If null then it is the current url
     * @var Url|View|string|null
     */
    public Url|View|string|null $submitUrl = null;

    /**
     * The target to submit to
     * Only required when submitAsync = false
     * currentwindow = Same browser window
     * newwindow = New browser window (tab)
     */
    #[ExpectedValues(values: ["currentwindow", "newwindow"])]
    public string $submitTarget = 'currentwindow';

    /**
     * Submit the form async
     * If false then the form will be submitted with native form submit features (new page load)
     * @var bool
     */
    public bool $submitAsync = true;

    /**
     * Submit the form async with raw data instead of POST/GET
     * Data can be retreived with Request::getBody()
     * This cannot be used when form contains file uploads
     * @var bool
     */
    public bool $submitAsyncRaw = false;

    /**
     * Validation message to show in the frontend
     * @var string|null
     */
    public string|null $validationMessage = null;

    /**
     * Submit the form with enter key
     * @var bool
     */
    public bool $submitWithEnter = false;

    /**
     * Allow browser autocomplete in this form
     * @var bool
     */
    public bool $autocomplete = false;

    /**
     * Form buttons are sticked to the bottom of the screen and always visible
     * @var bool
     */
    public bool $stickyFormButtons = false;

    /**
     * Group fields
     * @var array|null
     */
    protected ?array $fieldGroups = null;

    /**
     * Check if the form with the given name is submitted
     * @param string $formName
     * @return bool
     */
    public static function isFormSubmitted(string $formName): bool
    {
        $formName = "framelix-form-" . $formName;
        if (Request::getPost($formName) === '1') {
            return true;
        }
        if (Request::getGet($formName) === '1') {
            return true;
        }
        return false;
    }

    /**
     * Show form
     * @param bool $fillWithSubmittedData If true then fill form data with submitted data, if form isn't async and current request contains form data
     */
    final public function show(bool $fillWithSubmittedData = true): void
    {
        // if this form is already submitted without async, then validate and fill data before generating it
        if ($fillWithSubmittedData && !$this->submitAsync && self::isFormSubmitted($this->id)) {
            foreach ($this->fields as $field) {
                $field->defaultValue = $field->getSubmittedValue();
            }
            $this->validate(false);
        }
        $jsonData = JsonUtils::encode($this);
        $randomId = RandomGenerator::getRandomHtmlId();
        ?>
        <div id="<?= $randomId ?>"></div>
        <script>
          (function () {
            const form = FramelixForm.createFromPhpData(<?=$jsonData?>)
            form.render()
            $("#<?=$randomId?>").replaceWith(form.container)
          })()
        </script>
        <?php
    }

    /**
     * Get html
     * @return string
     */
    final public function getHtml(): string
    {
        ob_start();
        $this->show();
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Get form html attributes
     * @return HtmlAttributes
     */
    public function getHtmlAttributes(): HtmlAttributes
    {
        if ($this->htmlAttributes === null) {
            $this->htmlAttributes = new HtmlAttributes();
        }
        return $this->htmlAttributes;
    }

    /**
     * Add a field
     * @param Field $field
     */
    public function addField(Field $field): void
    {
        $field->form = $this;
        $this->fields[$field->name] = $field;
    }

    /**
     * Remove a field by name
     * @param string $name
     */
    public function removeField(string $name): void
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->form = null;
            unset($this->fields[$name]);
        }
    }

    /**
     * Add a button where you later can bind custom actions
     * @param string $actionId
     * @param string $buttonText
     * @param string|null $buttonIcon
     * @param ColorName $buttonColor
     * @param string|null $buttonTooltip
     */
    public function addButton(
        string $actionId,
        string $buttonText,
        ?string $buttonIcon = 'open_in_new',
        ColorName $buttonColor = ColorName::DEFAULT,
        ?string $buttonTooltip = null
    ): void {
        $this->buttons[] = [
            'type' => 'action',
            'action' => $actionId,
            'color' => $buttonColor,
            'buttonText' => Lang::get($buttonText),
            'buttonIcon' => $buttonIcon,
            'buttonTooltip' => $buttonTooltip ? Lang::get($buttonTooltip) : null
        ];
    }

    /**
     * Add a button to load a url
     * @param Url $url
     * @param string $buttonText
     * @param string|null $buttonIcon
     * @param ColorName $buttonColor
     * @param string|null $buttonTooltip
     */
    public function addLoadUrlButton(
        Url $url,
        string $buttonText = '__framelix_stop_edit__',
        ?string $buttonIcon = 'open_in_new',
        ColorName $buttonColor = ColorName::DEFAULT,
        ?string $buttonTooltip = null
    ): void {
        $this->buttons[] = [
            'type' => 'url',
            'url' => $url->getUrlAsString(),
            'color' => $buttonColor,
            'buttonText' => Lang::get($buttonText),
            'buttonIcon' => $buttonIcon,
            'buttonTooltip' => $buttonTooltip ? Lang::get($buttonTooltip) : null
        ];
    }

    /**
     * Add submit button
     * @param string $submitFieldName
     * @param string $buttonText
     * @param string|null $buttonIcon
     * @param ColorName $buttonColor
     * @param string|null $buttonTooltip
     */
    public function addSubmitButton(
        string $submitFieldName = 'save',
        string $buttonText = '__framelix_save__',
        ?string $buttonIcon = 'save',
        ColorName $buttonColor = ColorName::SUCCESS,
        ?string $buttonTooltip = null
    ): void {
        $this->buttons[] = [
            'type' => 'submit',
            'submitFieldName' => $submitFieldName,
            'color' => $buttonColor,
            'buttonText' => Lang::get($buttonText),
            'buttonIcon' => $buttonIcon,
            'buttonTooltip' => $buttonTooltip ? Lang::get($buttonTooltip) : null
        ];
    }

    /**
     * Add a field group
     * Each field in $fieldNames will be grouped under a collapsable container with $label
     * The group collapsable will be inserted before the first field in $fieldNames
     * @param string $id
     * @param string $label
     * @param string[] $fieldNames
     * @param bool $defaultState
     * @param bool $rememberState
     * @return void
     */
    public function addFieldGroup(
        string $id,
        string $label,
        array $fieldNames,
        bool $defaultState = true,
        bool $rememberState = true
    ): void {
        $this->fieldGroups[$id] = [
            'label' => $label,
            'fieldNames' => $fieldNames,
            'defaultState' => $defaultState,
            'rememberState' => $rememberState
        ];
    }

    /**
     * Remove field groupby given id
     * @param string $id
     * @return void
     */
    public function removeFieldGroup(string $id): void
    {
        if ($this->fieldGroups) {
            unset($this->fieldGroups[$id]);
        }
    }

    /**
     * Get submitted values
     * @return array
     */
    public function getSubmittedValues(): array
    {
        $arr = [];
        foreach ($this->fields as $fieldName => $field) {
            $arr[$fieldName] = $field->getSubmittedValue();
        }
        return $arr;
    }

    /**
     * Get converted submitted values
     * @return array
     */
    public function getConvertedSubmittedValues(): array
    {
        $arr = [];
        foreach ($this->fields as $fieldName => $field) {
            $arr[$fieldName] = $field->getConvertedSubmittedValue();
        }
        return $arr;
    }

    /**
     * Reset the cache for getConvertedSubmittedValue of all fields
     * This is mostly requird for unit tests
     * @return void
     */
    public function resetConvertedSubmittedValueCache(): void
    {
        foreach ($this->fields as $field) {
            $field->resetConvertedSubmittedValueCache();
        }
    }

    /**
     * Set all storable values that exist as properties with corresponing field names
     * @param Storable $storable
     */
    public function setStorableValues(Storable $storable): void
    {
        foreach ($this->fields as $field) {
            $fieldValue = $field->getConvertedSubmittedValue();
            $nameParts = ArrayUtils::splitKeyString($field->name);
            $storableSchemaProperty = Storable::getStorableSchemaProperty($storable, $nameParts[0]);
            if (!$storableSchemaProperty) {
                continue;
            }
            if ($storableSchemaProperty->storableClass || $storableSchemaProperty->arrayStorableClass) {
                $storableClass = $storableSchemaProperty->storableClass ? new $storableSchemaProperty->storableClass(
                ) : null;
                $arrayStorableClass = $storableSchemaProperty->arrayStorableClass ? new $storableSchemaProperty->arrayStorableClass(
                ) : null;
                // skip storable files, they can be handled with this->storeWithFiles()
                if ($field instanceof File && ($storableClass instanceof StorableFile || $arrayStorableClass instanceof StorableFile)) {
                    continue;
                }
            }
            // in case of raw json (no specific storable type), merge arrays keys instead of override complete property
            if ($storableSchemaProperty->internalType === 'mixed' && !$storableSchemaProperty->arrayStorableClass && !$storableSchemaProperty->arrayStorableInterface) {
                array_shift($nameParts);
                $storableValue = $storable->{$storableSchemaProperty->name} ?? [];
                if (!is_array($storableValue)) {
                    $storableValue = [];
                }
                ArrayUtils::setValue($storableValue, $nameParts, $fieldValue);
                $storable->{$storableSchemaProperty->name} = $storableValue;
                continue;
            }
            $storable->{$storableSchemaProperty->name} = $fieldValue;
        }
    }

    /**
     * Store a storable including uploaded/deleted files in the form
     * @param Storable $storable
     */
    public function storeWithFiles(Storable $storable): void
    {
        $newFiles = [];
        foreach ($this->fields as $field) {
            $nameParts = ArrayUtils::splitKeyString($field->name);
            $storableSchemaProperty = Storable::getStorableSchemaProperty($storable, $nameParts[0]);
            if (!$storableSchemaProperty) {
                continue;
            }
            if ($storableSchemaProperty->storableClass || $storableSchemaProperty->arrayStorableClass) {
                $storableClass = $storableSchemaProperty->storableClass ? new $storableSchemaProperty->storableClass(
                ) : null;
                $arrayStorableClass = $storableSchemaProperty->arrayStorableClass ? new $storableSchemaProperty->arrayStorableClass(
                ) : null;
                if ($field instanceof File && ($storableClass instanceof StorableFile || $arrayStorableClass instanceof StorableFile)) {
                    $files = UploadedFile::createFromSubmitData($storableSchemaProperty->name);
                    if ($files) {
                        if ($arrayStorableClass) {
                            // array, just adding new files
                            $existingValues = $storable->{$storableSchemaProperty->name} ?? [];
                            foreach ($files as $file) {
                                /** @var StorableFile $storableFile */
                                $storableFile = $storableSchemaProperty->arrayStorableClass ? new $storableSchemaProperty->arrayStorableClass(
                                ) : new $storableSchemaProperty->storableClass();
                                $storableFile->store($file);
                                $existingValues[] = $storableFile;
                                $newFiles[] = $storableFile;
                            }
                            $storable->{$storableSchemaProperty->name} = $existingValues;
                        } else {
                            // non array, override file
                            /** @var StorableFile|null $existingValue */
                            $existingValue = $storable->{$storableSchemaProperty->name};
                            if ($existingValue) {
                                $existingValue->filename = $files[0]->name;
                                $existingValue->store($files[0]);
                            } else {
                                $storableFile = new $storableSchemaProperty->storableClass();
                                $storableFile->store($files[0]);
                                $newFiles[] = $storableFile;
                                $storable->{$storableSchemaProperty->name} = $storableFile;
                            }
                        }
                    }
                    /** @var StorableFile|StorableFile[] $existingValues */
                    $existingValues = $storable->{$storableSchemaProperty->name};
                    if ($existingValues) {
                        $deleteFlags = $field->getSubmittedValue();
                        if (is_array($deleteFlags)) {
                            /** @var StorableFile[] $storableFiles */
                            $storableFiles = [];
                            if (is_array($existingValues)) {
                                foreach ($existingValues as $existingValue) {
                                    $storableFiles[$existingValue->id] = $existingValue;
                                }
                            } else {
                                $storableFiles[$existingValues->id] = $existingValues;
                            }
                            foreach ($deleteFlags as $id => $flag) {
                                if ($flag === "0" && isset($storableFiles[$id])) {
                                    $storableFile = $storableFiles[$id];
                                    $storableFile->delete();
                                    unset($storableFiles[$id]);
                                }
                            }
                            if ($arrayStorableClass) {
                                $storable->{$storableSchemaProperty->name} = $storableFiles;
                            } else {
                                $storable->{$storableSchemaProperty->name} = $storableFiles ? reset(
                                    $storableFiles
                                ) : null;
                            }
                        }
                    }
                }
            }
        }
        $storable->store();
        // after storing the storable, we can assign the storable to all uploaded files
        if ($newFiles) {
            foreach ($newFiles as $storableFile) {
                $storableFile->assignedStorable = $storable;
                $storableFile->store();
            }
        }
    }

    /**
     * Validate the form
     * @param bool $asyncValidation If true and validation messages exist, then output messages as json and stop code execution
     * @return bool
     */
    public function validate(bool $asyncValidation = true): bool
    {
        $success = true;
        $messages = [];
        foreach ($this->fields as $field) {
            $validation = $field->validate();
            if ($validation !== true) {
                $field->validationMessage = $validation;
                $messages[$field->name] = $validation;
                $success = false;
            }
        }
        if (!$success && $asyncValidation && Request::isAsync()) {
            Response::showFormValidationErrorResponse($messages);
        }
        return $success;
    }

    /**
     * Get json data
     * @return array
     */
    public function jsonSerialize(): array
    {
        $properties = [];
        foreach ($this as $key => $value) {
            if ($key === 'submitUrl' && $value instanceof View) {
                $value = View::getUrl(get_class($value));
            }
            $properties[$key] = $value;
        }
        return [
            'properties' => $properties
        ];
    }
}