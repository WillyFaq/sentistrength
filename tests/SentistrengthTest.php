<?php
// test/BasicTest.php

use wfphpnlp\Sentistrength;

class SentistrengthTest extends PHPUnit\Framework\TestCase
{

    public $result;

    public function setUp()
    {
        $config = array(
            'negation_conf' => true,
            'booster_conf' => true,
            'ungkapan_conf' => true,
            'consecutive_conf' => true,
            'repeated_conf' => true,
            'emoticon_conf' => true,
            'question_conf' => true,
            'exclamation_conf' => true,
            'punctuation_conf' => true,
            );
        $input = "agnezmo pintar dan cantik sekali tetapi lintah darat :)";
        $obj = new Sentistrength();
        $this->result = $obj->main($input);
    }
    /** @test */
    public function testHasilKelas()
    {
        $kelas = "Positif";
        $this->assertEquals($kelas, $this->result['kelas']);
    }

    public function testHasilClassifiedText()
    {
        $classified_text = "agnezmo pintar [4] dan cantik [6] sekali tetapi lintah darat [-4] :) [3]";
        $this->assertEquals($classified_text, $this->result['classified_text']);
    }

    public function testHasilMaxPositive()
    {
        $max_positive = 6;
        $this->assertEquals($max_positive, $this->result['max_positive']);
    }

    public function testHasilMaxNegative()
    {
        $max_negative = -4;
        $this->assertEquals($max_negative, $this->result['max_negative']);
    }
}