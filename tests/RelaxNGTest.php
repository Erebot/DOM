<?php
/*
    This file is part of Erebot.

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

class RelaxNGTest extends PHPUnit_Framework_TestCase
{
    private $useErrors;

    public function setUp()
    {
        parent::setUp();
        $this->useErrors = libxml_use_internal_errors(true);
    }

    public function tearDown()
    {
        libxml_use_internal_errors($this->useErrors);
        parent::tearDown();
    }

    public function testValidationSuccess()
    {
        $dom = new \Erebot\DOM();
        $this->assertTrue($dom->load(__DIR__ . DIRECTORY_SEPARATOR . 'ok.xml'));
        $this->assertTrue($dom->relaxNGValidate(__DIR__ . DIRECTORY_SEPARATOR . 'test.rng'));
        $this->assertSame(0, count($dom->getErrors()));
    }

    public function testValidationFailure()
    {
        $dom = new \Erebot\DOM();
        $this->assertTrue($dom->load(__DIR__ . DIRECTORY_SEPARATOR . 'nok.xml'));
        $this->assertFalse($dom->relaxNGValidate(__DIR__ . DIRECTORY_SEPARATOR . 'test.rng'));
        $errors = $dom->getErrors();
        $this->assertSame(1, count($errors));

        // Inspect the error's contents.
        $this->assertSame(LIBXML_ERR_ERROR, $errors[0]->level);
        $this->assertSame('Wrong answer to life, the universe and everything', $errors[0]->message);
        $this->assertSame(__DIR__ . DIRECTORY_SEPARATOR . 'nok.xml', $errors[0]->file);
        $this->assertSame(2, $errors[0]->line);
        $this->assertSame('/Root', $errors[0]->path);
    }
}
