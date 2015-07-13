<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Morris Jobke <hey@morrisjobke.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace ownCloud;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

require('vendor/autoload.php');

/**
 * Class Release
 */

class Release {

    /**
     * @var \DateTime
     */
    protected $releaseDate;

    /**
     * @var String
     */
    protected $version;

    /**
     * @var \DateTime Day 1 of the release cycle
     */
    protected $startDate;

    /**
     * @var
     */
    protected $dates;

    /**
     * @param String $version
     */
    public function __construct($version) {
        $this->version = $version;
    }

    /**
     * @return \DateTime
     */
    public function getDayAfterRelease() {
        return $this->releaseDate->add(\DateInterval::createFromDateString('1 day'));
    }

    /**
     * @return string version string of the previous release
     * @throws \Exception if this is a major release
     */
    public function getPreviousVersion() {
        $version = explode('.', $this->version);
        if ($version[2] === '0') {
            throw new \Exception('No previous version available.');
        }

        $patchLevel = (int)$version[2];
        $patchLevel -= 1;
        $version[2] = $patchLevel;

        return implode('.', $version);

    }

    public function isMinorRelease() {
        $version = explode('.', $this->version);
        return $version[2] !== '0';
    }

    /**
     * @param \DateTime|String $date
     */
    public function setStartDate($date) {
        if (!$date instanceof \DateTime) {
            $date = $this->parseDate($date);
        }
        $date->sub(\DateInterval::createFromDateString('1 day'));
        $this->startDate = $date;
    }

    public function hasStartDate() {
        return $this->startDate instanceof \DateTime;
    }

    public function calculateDates($rules, $specialDates) {
        $baseDate = clone $this->startDate;
        $lastDay = 0;
        foreach ($rules as $day => $info) {
            if ($day === 'cycle') {
                continue;
            }
            if (isset($specialDates[$day])) {
                $date = $this->parseDate($specialDates[$day]);
            } else {
                $date = $this->calculateDate($baseDate, ((int)$day) - $lastDay);
            }


            $baseDate = clone $date;
            $lastDay = (int)$day;

            if ($day === (int)$rules['cycle']) {
                $this->releaseDate = $date;
            }

            $this->dates[] = [
                'date' => $date,
                'info' => $info,
            ];
        }
    }

    public function getDates() {
        $result = [];
        foreach($this->dates as $date) {
            $formattedDate = $date['date']->format('Y-m-d');
            $result[$formattedDate] = $date['info'];
            $result[$formattedDate]['date'] = $date['date'];
            $result[$formattedDate]['title'] = $this->version . ' ' . $result[$formattedDate]['title'];
        }

        ksort($result);
        return $result;
    }

    /**
     * @param String $date
     * @return \DateTime
     */
    protected function parseDate($date) {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $date . '00:00:00', new \DateTimeZone('UTC'));
    }

    /**
     * @param \DateTime $baseDate
     * @param String $days
     * @return \DateTime
     */
    protected function calculateDate($baseDate, $days) {
        $baseDate->add(\DateInterval::createFromDateString($days . ' day'));
        return $baseDate;
    }
}

$data = json_decode(file_get_contents('schedule.json'), true);
/* @var Release[] $dates */
$releaseDates = [];
$releases = array_keys($data['releases']);

foreach ($releases as $releaseVersion) {

    $release = new Release($releaseVersion);

    $specialDates = $data['releases'][$releaseVersion];

    if (isset($data['releases'][$releaseVersion]["1"])) {
        $release->setStartDate(
            $data['releases'][$releaseVersion]["1"]
        );
    }

    if ($release->isMinorRelease()) {

        $rules = $data['minor'];

        if (!$release->hasStartDate()) {
            $release->setStartDate(
                $releaseDates[$release->getPreviousVersion()]->getDayAfterRelease()
            );
        }

    } else {

        $rules = $data['major'];

        if (!$release->hasStartDate()) {
            throw new \Exception('No start date for major release ' . $releaseVersion);
        }

    }

    $release->calculateDates($rules, $specialDates);

    $releaseDates[$releaseVersion] = $release;
}

$vCalendar = new Calendar('owncloud.org');


foreach($releaseDates as $releaseVersion => $release) {
    $dates = $release->getDates();
    foreach($dates as $date => $info) {
        $vEvent = new Event();
        $vEvent
            ->setDtStart($info['date'])
            ->setDtEnd($info['date'])
            ->setNoTime(true)
            ->setUniqueId(sha1($date . ' : ' . $info['title'] . ' - ' . $info['comment']))
            ->setSummary($info['title'])
            ->setDescription($info['comment']);
        $vCalendar->addComponent($vEvent);
        echo $date . ' : ' . $info['title'] . ' - ' . $info['comment'] . PHP_EOL;
    }
}

file_put_contents('ownCloud-releases.ical', $vCalendar->render());
