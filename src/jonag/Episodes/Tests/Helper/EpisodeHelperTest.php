<?php

namespace jonag\Episodes\Tests\Helper;


use jonag\Episodes\Helper\EpisodeHelper;

class EpisodeHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $fileName
     * @param $expected
     *
     * @dataProvider fileNameProvider
     */
    public function testParse($fileName, $expected)
    {
        $actual = EpisodeHelper::parseFileName($fileName);

        if ($actual === false) {
            $this->assertEquals($expected, $actual);
        } else {
            $this->assertEquals($expected['showName'], $actual->getShowName());
            $this->assertEquals($expected['season'], $actual->getSeason());
            $this->assertEquals($expected['episode'], $actual->getEpisode());
            $this->assertEquals($expected['releaseName'], $actual->getReleaseName());
            $this->assertEquals($expected['proper'], $actual->isProper());
            $this->assertEquals($expected['sample'], $actual->isSample());
        }
    }

    public function fileNameProvider()
    {
        return [
            ['Angie.Tribeca.S02E02.720p.HDTV.X264-DIMENSION', ['showName' => 'Angie Tribeca', 'season' => 2, 'episode' => 2, 'releaseName' => 'Angie.Tribeca.S02E02.720p.HDTV.X264-DIMENSION', 'proper' => false, 'sample' => false]],
            ['Angie.Tribeca.720p.HDTV.X264-DIMENSION', false],
            ['angie.tribeca.S01E08.720p.HDTV.X264-DIMENSION', ['showName' => 'Angie Tribeca', 'season' => 1, 'episode' => 8, 'releaseName' => 'angie.tribeca.S01E08.720p.HDTV.X264-DIMENSION', 'proper' => false, 'sample' => false]],
            ['Angie.Tribeca.S01E07.720p.HDTV.X264-DIMENSION[rarbg]', ['showName' => 'Angie Tribeca', 'season' => 1, 'episode' => 7, 'releaseName' => 'Angie.Tribeca.S01E07.720p.HDTV.X264-DIMENSION', 'proper' => false, 'sample' => false]],
            ['Angie.Tribeca.S01E06.PROPER.720p.HDTV.X264-DIMENSION', ['showName' => 'Angie Tribeca', 'season' => 1, 'episode' => 6, 'releaseName' => 'Angie.Tribeca.S01E06.PROPER.720p.HDTV.X264-DIMENSION', 'proper' => true, 'sample' => false]],
            ['Angie.Tribeca.S01E05.720p.HDTV.X264-DIMENSION-sample', ['showName' => 'Angie Tribeca', 'season' => 1, 'episode' => 5, 'releaseName' => 'Angie.Tribeca.S01E05.720p.HDTV.X264-DIMENSION-sample', 'proper' => false, 'sample' => true]],
            ['The.Flash.S03E01', ['showName' => 'The Flash', 'season' => 3, 'episode' => 1, 'releaseName' => 'The.Flash.S03E01', 'proper' => false, 'sample' => false]]
        ];
    }
}
