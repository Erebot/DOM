<?php
/*
    This file is part of Erebot, a modular IRC bot written in PHP.

    Copyright © 2010 François Poirotte

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Erebot\DOM;

/**
 * \brief
 *      An extension of PHP's DomDocument class that implements
 *      Schematron validation on top of RelaxNG/XML Schema.
 *
 * This class also makes it easier to deal with libxml errors,
 * by providing methods to clear or retrieve errors directly.
 *
 * \see
 *      http://php.net/domdocument
 *
 * \see
 *      http://www.schematron.com/
 */
class DOM extends \DomDocument
{
    /// Stores the LibXMLError errors raised during validation.
    protected $errors;

    /**
     * Constructs a new DOM document.
     *
     * \param string $version
     *      (optional) XML version to use.
     *
     * \param string $encoding
     *      (optional) Encoding for the document.
     */
    public function __construct($version = null, $encoding = null)
    {
        $this->clearErrors();
        if ($version === null && $encoding === null) {
            parent::__construct();
        } elseif ($encoding === null) {
            parent::__construct($version);
        } else {
            parent::__construct($version, $encoding);
        }
    }

    /**
     * Validates the current document against a RelaxNG schema,
     * optionally validates embedded Schematron rules too.
     *
     * \param string $filename
     *      Path to the RelaxNG schema to use for validation.
     *
     * \param bool $schematron
     *      (optional) Whether embedded Schematron rules
     *      should be validated too (\b true) or not (\b false).
     *      The default is to also do $schematron validation.
     *
     * \retval bool
     *      \b true if the document validates,
     *      \b false otherwise.
     */
    public function relaxNGValidate($filename, $schematron = true)
    {
        $success = parent::relaxNGValidate($filename);
        return $this->schematronValidation(
            'file',
            $filename,
            'RNG',
            $success,
            $schematron
        );
    }

    /**
     * Validates the current document against a RelaxNG schema,
     * optionally validates embedded Schematron rules too.
     *
     * \param string $source
     *      Source of the RelaxNG schema to use for validation.
     *
     * \param bool $schematron
     *      (optional) Whether embedded Schematron rules
     *      should be validated too (\b true) or not (\b false).
     *      The default is to also do $schematron validation.
     *
     * \retval bool
     *      \b true if the document validates,
     *      \b false otherwise.
     */
    public function relaxNGValidateSource($source, $schematron = true)
    {
        $success = parent::relaxNGValidateSource($source);
        return $this->schematronValidation(
            'string',
            $source,
            'RNG',
            $success,
            $schematron
        );
    }

    /**
     * Validates the current document against an XML schema,
     * optionally validates embedded Schematron rules too.
     *
     * \param string $filename
     *      Path to the XML schema to use for validation.
     *
     * \param bool $schematron
     *      (optional) Whether embedded Schematron rules
     *      should be validated too (\b true) or not (\b false).
     *      The default is to also do $schematron validation.
     *
     * \retval bool
     *      \b true if the document validates,
     *      \b false otherwise.
     */
    public function schemaValidate($filename, $schematron = true)
    {
        $success = parent::schemaValidate($filename);
        return $this->schematronValidation(
            'file',
            $filename,
            'XSD',
            $success,
            $schematron
        );
    }

    /**
     * Validates the current document against an XML schema,
     * optionally validates embedded Schematron rules too.
     *
     * \param string $source
     *      Source of the XML schema to use for validation.
     *
     * \param bool $schematron
     *      (optional) Whether embedded Schematron rules
     *      should be validated too (\b true) or not (\b false).
     *      The default is to also do $schematron validation.
     *
     * \retval bool
     *      \b true if the document validates,
     *      \b false otherwise.
     */
    public function schemaValidateSource($source, $schematron = true)
    {
        $success = parent::schemaValidateSource($source);
        return $this->schematronValidation(
            'string',
            $source,
            'XSD',
            $success,
            $schematron
        );
    }

    /**
     * Proceed to the actual Schematron validation.
     *
     * \param string $source
     *      Source of the Schematron rules:
     *      - 'file' when $data refers to a filename.
     *      - 'string' when $data refers to an in-memory string.
     *
     * \param string $data
     *      Schematron data. The interpretation of this parameter
     *      depends on the value of the $source parameter.
     *
     * \param string $schemaSource
     *      The original schema type used to validate the document.
     *      This is "XSD" for XML Schema documents or "RNG" for
     *      RelaxNG schemas.
     *
     * \param bool $success
     *      Whether the original validation process succeeded
     *      (\b true) or not (\b false). This is used to abort the
     *      Schematron validation process earlier.
     *
     * \param bool $schematron
     *      Whether a Schematron validation pass should occur
     *      (\b true) or not (\b false).
     *
     * \retval bool
     *      Whether the overall validation passed (\b true)
     *      or not (\b false).
     *
     * \note
     *      In case validation failed, ::Erebot::DOM::DOM::getErrors()
     *      may be used to retrieve further information on why
     *      it failed.
     */
    protected function schematronValidation(
        $source,
        $data,
        $schemaSource,
        $success,
        $schematron
    ) {
        try {
            $base = dirname(__DIR__) .
                    DIRECTORY_SEPARATOR . 'data' .
                    DIRECTORY_SEPARATOR;
            $xsl1 = $base . $schemaSource . '2Schtrn.xsl';
            $xsl2 = $base . 'schematron-custom.xsl';
            $skeleton = $base . 'skeleton1-5.xsl';
        } catch (Exception $e) {
            return false;
        }

        $quiet      = !libxml_use_internal_errors();
        if (!$quiet) {
            $this->errors = array_merge($this->errors, libxml_get_errors());
            libxml_clear_errors();
        }
        if (!$success || !$schematron) {
            return $success;
        }

        $schema     = new \DomDocument();
        if ($source == 'file') {
            $success = $schema->load($data);
        } else {
            $success = $schema->loadXML($data);
        }

        if (!$quiet) {
            $this->errors = array_merge($this->errors, libxml_get_errors());
            libxml_clear_errors();
        }
        if (!$success) {
            return false;
        }

        $processor  = new \XSLTProcessor();
        $extractor  = new \DomDocument();
        $success    = $extractor->loadXML(file_get_contents($xsl1));
        if (!$quiet) {
            $this->errors = array_merge($this->errors, libxml_get_errors());
            libxml_clear_errors();
        }
        if (!$success) {
            return false;
        }

        $processor->importStylesheet($extractor);
        $result = $processor->transformToDoc($schema);
        if ($result === false) {
            return false;
        }

        $validator  = new \DomDocument();
        $xsl2       = str_replace(
            '@xsl_skeleton@',
            'data:;base64,' . base64_encode(file_get_contents($skeleton)),
            file_get_contents($xsl2)
        );
        $success    = $validator->loadXML($xsl2);
        if (!$quiet) {
            $this->errors = array_merge($this->errors, libxml_get_errors());
            libxml_clear_errors();
        }
        if (!$success) {
            return false;
        }

        $processor->importStylesheet($validator);
        $result = $processor->transformToDoc($result);
        if ($result === false) {
            return false;
        }

        $processor = new \XSLTProcessor();
        $processor->importStylesheet($result);
        $result = $processor->transformToDoc($this);
        if ($result === false) {
            return false;
        }

        $root   = $result->firstChild;
        $valid  = true;
        foreach ($root->childNodes as $child) {
            if ($child->nodeType != XML_ELEMENT_NODE) {
                continue;
            }

            if ($child->localName != 'assertionFailure') {
                continue;
            }

            $valid = false;

            // If running in quiet mode, don't report errors.
            if ($quiet) {
                continue;
            }

            $error = new \LibXMLError();
            $error->level   = LIBXML_ERR_ERROR;
            $error->code    = 0;
            $error->column  = 0;
            $error->message = '';
            $error->file    = $this->documentURI;
            $error->line    = 0;
            $error->path    = '';

            foreach ($child->childNodes as $subchild) {
                if ($subchild->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                if ($subchild->localName == 'description' && $error->message == '') {
                    $error->message = $subchild->textContent;
                } elseif ($subchild->localName == 'location' && $error->path == '') {
                    $error->path = $subchild->textContent;
                }
            }
            $this->errors[] = $error;
        }
        return $valid;
    }

    /**
     * Validates the document against its DTD.
     *
     * \param bool $schematron
     *      (optional) The value of this parameter is currently
     *      unused as there is no way to embed Schematron rules
     *      into a DTD. This parameter is provided only to make
     *      the API uniform accross the different methods of this
     *      class.
     *
     * \retval bool
     *      \b true if the document validates,
     *      \b false otherwise.
     *
     * \note
     *      This method is the same as the regular DomDocument::validate()
     *      method excepts that it captures errors so they can be later
     *      retrieved with the ::Erebot::DOM::DOM::getErrors() method.
     *
     * \note
     *      There is currently no way to embed Schematron rules
     *      into a Document Type Declaration. Therefore, the
     *      value of the $schematron parameter is always ignored.
     */
    public function validate($schematron = false)
    {
        $success    = parent::validate();
        $quiet      = !libxml_use_internal_errors();
        if (!$quiet) {
            $this->errors = array_merge($this->errors, libxml_get_errors());
            libxml_clear_errors();
        }
        return $success;
    }

    /**
     * Returns an array of errors raised during validation.
     *
     * \retval array
     *      Array of LibXMLError objets raised during validation.
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Clears all validation errors.
     */
    public function clearErrors()
    {
        $this->errors = array();
    }
}
