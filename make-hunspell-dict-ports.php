<?php

define('MAINTAINERS', 'nox openmaintainer');

function burn($sException, $sMsg = null)
{
    throw new $sException($sMsg);
}

function build_port($locale, $version, $lang, $checksums)
{
    ob_start();
    eval(file_get_contents(__FILE__, null, null, __COMPILER_HALT_OFFSET__));
    return ob_get_clean();
}

$oDoc = new DOMDocument;
@$oDoc->loadHTMLFile('http://wiki.services.openoffice.org/wiki/Dictionaries') or
    burn('UnexceptedValueException', 'Could not load dictionaries page.');

$oContent = $oDoc->getElementById('bodyContent');
$oContent !== false or burn('UnexceptedValueException') or
    burn('UnexceptedValueException', 'Could not find page content.');

$oXPath = new DOMXPath($oDoc);

$iDictCount = 0;

foreach ($oXPath->query('./h2', $oContent) as $oTitle) {
    $sLang = trim($oTitle->textContent);
    echo 'Reading "', $sLang, '" section', "\n";

    echo 'Searching dictionary link...';
    $oLinks = $oXPath->query(
        './/li/a[starts-with(@href, "http://ftp.services.openoffice.org/pub/OpenOffice.org/contrib/dictionaries/")]',
        $oTitle->nextSibling->nextSibling);
    if (!$oLinks->length) {
        echo " not found\n\n";
        continue;
    }
    
    echo ' found ', $oLinks->length, " canditate(s)\n";
    foreach ($oLinks as $oLink) {
        $sHREF = $oLink->getAttribute('href');
        $sLocale = basename($sHREF, '.zip');
        if (!preg_match('/^[a-z]{2,3}_[A-Z]{2}$/', $sLocale))
            continue;

        $sName = $oLink->textContent;
        if (strpos($sName, 'Spelling') !== false)
            $sName = $sLang;
        preg_replace('/\( *([^)]*?) *\)/', '(\1)', $sName);

        $aMatches = array();
        preg_match('/\d{4}-\d{2}-\d{2}/',
            trim($oLink->nextSibling->textContent), $aMatches);
        $sVersion = $aMatches[0];

        echo "\t", $sName, ': ', $oLink->getAttribute('href'),
            ' (', $sVersion, ")\n";

        $sZIP = file_get_contents($sHREF);
        $aChecksums = array(
            'md5'       => md5($sZIP),
            'sha1'      => sha1($sZIP),
            'rmd160'    => openssl_digest($sZIP, 'rmd160'),
        );

        $sPort = 'hunspell-dict-' . $sLocale;
        if (!is_dir($sPort))
            mkdir($sPort, 0755);
        file_put_contents($sPort . '/Portfile', build_port(
            $sLocale, $sVersion, $sName, $aChecksums));

        ++$iDictCount;
    }
    echo "\n";
}

echo 'Found ', $iDictCount, " dictionary(ies)\n";

__halt_compiler();?>
# $Id$

PortSystem      1.0
PortGroup       hunspelldict 1.0

hunspelldict.setup <?=$locale?> <?=$version?> {<?=$lang?>} ooo
maintainers     <?=MAINTAINERS, "\n"?>

checksums       md5     <?=$checksums['md5']?> \
                sha1    <?=$checksums['sha1']?> \
                rmd160  <?=$checksums['rmd160'], "\n"?>
