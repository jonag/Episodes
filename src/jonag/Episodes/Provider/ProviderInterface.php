<?php

namespace jonag\Episodes\Provider;

use Symfony\Component\Console\Style\OutputStyle;

interface ProviderInterface
{
    /**
     * @param \Symfony\Component\Console\Style\OutputStyle $io
     * @param \SplFileInfo                                 $fileInfo
     * @return null|string
     */
    public function findSubtitleForFile(OutputStyle $io, \SplFileInfo $fileInfo);
}
