<?php

namespace Apps\Fintech\Packages\Mf\Tools\Patterns;

use Apps\Fintech\Packages\Mf\Tools\Patterns\Model\AppsFintechMfToolsPatterns;
use System\Base\BasePackage;

class MfToolsPatterns extends BasePackage
{
    protected $modelToUse = AppsFintechMfToolsPatterns::class;

    protected $packageName = 'mftoolspatterns';

    public $mftoolspatterns;

    public function getMfToolsPatternsById($id)
    {
        $mftoolspatterns = $this->getById($id);

        if ($mftoolspatterns) {
            //
            $this->addResponse('Success');

            return;
        }

        $this->addResponse('Error', 1);
    }

    public function addMfToolsPatterns($data)
    {
        if (!$this->preCheckData($data)) {
            return false;
        }

        if ($this->add($data)) {
            $this->addResponse('Pattern Added');
        } else {
            $this->addResponse('Error Adding Pattern', 1);
        }
    }

    public function updateMfToolsPatterns($data)
    {
        if (!$this->preCheckData($data)) {
            return false;
        }

        $mftoolspatterns = $this->getById($data['id']);

        if ($mftoolspatterns) {
            $data = array_merge($mftoolspatterns, $data);

            if ($this->update($data)) {
                $this->addResponse('Pattern updated');

                return;
            }
        }

        $this->addResponse('Error', 1);
    }

    protected function preCheckData(&$data)
    {
        if ($data['pattern'] === '') {
            $this->addResponse('Please provide correct pattern', 1);

            return;
        }

        $patterns = explode(',', $data['pattern']);

        if (count($patterns) === 0) {
            $this->addResponse('Please provide correct pattern', 1);

            return;
        }

        array_walk($patterns, function(&$pattern, $index) use (&$patterns) {
            if (trim($pattern) !== '') {
                $pattern = (float) $pattern;
            } else {
                unset($patterns[$index]);
            }
        });

        if (!checkCtype($data['name'], 'alnum', ['_'])) {
            $this->addResponse('Name cannot have special chars or numbers.', 1);

            return false;
        }

        $data['pattern'] = $patterns;

        return true;
    }

    public function removeMfToolsPatterns($id)
    {
        $mftoolspatterns = $this->getById($id);

        if ($mftoolspatterns) {
            if ($this->remove($id)) {
                $this->addResponse('Success');

                return true;
            }
        }

        $this->addResponse('Error', 1);
    }

    public function generatePattern($data)
    {
        if (!isset($data['source']) ||
            (isset($data['source']) && $data['source'] === '')
        ) {
            $this->addResponse('Please provide correct source', 1);

            return;
        }

        if ($data['source'] !== 'fixed_linear' && $data['source'] !== 'fixed_random') {
            $this->addResponse('Please provide correct source', 1);

            return;
        }

        if (!isset($data['numberOfDays']) ||
            (isset($data['numberOfDays']) && $data['numberOfDays'] === '')
        ) {
            $this->addResponse('Please provide correct number of days', 1);

            return;
        }

        if (!isset($data['totalPercent']) ||
            (isset($data['totalPercent']) && $data['totalPercent'] === '')
        ) {
            $this->addResponse('Please provide correct total percent', 1);

            return;
        }

        $data['numberOfDays'] = (int) $data['numberOfDays'];
        $data['totalPercent'] = (float) $data['totalPercent'];

        if ($data['numberOfDays'] === 0) {
            $this->addResponse('Please provide correct number of days', 1);

            return;
        }

        if ($data['totalPercent'] == 0) {
            $this->addResponse('Please provide correct total percent', 1);

            return;
        }

        $data['startValue'] = (float) $data['startValue'];

        $perDay = $data['totalPercent'] / $data['numberOfDays'];

        if ($data['startValue'] == 0) {
            $patterns[0] = 0;
        }

        for ($numberOfDays=0; $numberOfDays < $data['numberOfDays']; $numberOfDays++) {
            $patterns[$numberOfDays + 1] = numberFormatPrecision($perDay * ($numberOfDays + 1), 2);

            if ($data['startValue'] > 0) {
                $patterns[$numberOfDays + 1] = numberFormatPrecision($data['startValue'] + $patterns[$numberOfDays + 1], 2);
            }
        }

        if ($data['source'] === 'fixed_linear') {
            $this->addResponse('Ok', 0, ['pattern' => array_values($patterns)]);

            return true;
        }

        if (!isset($data['between'])) {
            $data['between'] = ['-5', '5'];
        } else {
            $data['between'] = explode(',', $data['between']);
        }

        foreach ($patterns as $index => &$pattern) {
            if ($index === 0 ||
                $index === $this->helper->lastKey($patterns)
            ) {
                continue;
            }

            $pattern = numberFormatPrecision($pattern + (mt_rand((float) $data['between'][0] * 100, (float) $data['between'][1] * 100) / 100), 2);
        }

        $this->addResponse('Ok', 0, ['pattern' => array_values($patterns)]);

        return true;
    }
}