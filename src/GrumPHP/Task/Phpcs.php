<?php

namespace GrumPHP\Task;

use GrumPHP\Exception\RuntimeException;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;

/**
 * Phpcs task
 */
class Phpcs extends AbstractExternalTask
{
    const COMMAND_NAME = 'phpcs';

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return array(
            'standard' => 'PSR2',
            'show_warnings' => true,
            'tab_width' => null,
            'ignore_patterns' => array(),
            'sniffs' => array(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandLocation()
    {
        return $this->externalCommandLocator->locate(self::COMMAND_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function canRunInContext(ContextInterface $context)
    {
        return ($context instanceof GitPreCommitContext || $context instanceof RunContext);
    }

    /**
     * {@inheritdoc}
     */
    public function run(ContextInterface $context)
    {
        $files = $context->getFiles()->name('*.php');
        if (0 === count($files)) {
            return;
        }

        $config = $this->getConfiguration();
        $this->processBuilder->setArguments(array(
            $this->getCommandLocation(),
            '--standard=' . $config['standard'],
        ));

        if (!$config['show_warnings']) {
            $this->processBuilder->add('--warning-severity=0');
        }

        if ($config['tab_width']) {
            $this->processBuilder->add('--tab-width=' . $config['tab_width']);
        }

        if (count($config['sniffs'])) {
            $this->processBuilder->add('--sniffs=' . implode(',', $config['sniffs']));
        }

        if (count($config['ignore_patterns'])) {
            $this->processBuilder->add('--ignore=' . implode(',', $config['ignore_patterns']));
        }

        foreach ($files as $file) {
            $this->processBuilder->add($file);
        }

        $process = $this->processBuilder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException($process->getOutput());
        }
    }
}
