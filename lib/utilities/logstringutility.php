<?php

/**
 *
 * @package metin2shop
 * @subpackage utilities
 * @author timo.huber
 * @version $Id: logstringutility.php 2769 2015-11-05 07:06:58Z timo.huber $
 */
class LogstringUtility
{

    /**
     * Returns a one-line string representation of the given variable.
     * Useful to keep log messages a one-liner even for dumped arrays.
     * Note: Entries with passwords are removed (depending on entry's keyname)
     *
     * @uses AppController::_removePasswordsFromArray()
     * @param mixed $variable Variable to convert
     * @return string Variable's string representation
     */
    public function toLogString($variable)
    {
        $data = $variable;
        if (is_array($data)) {
            $this->removePasswordsFromArray($data);
        }
        $string = var_export($data, true);
        $string = preg_replace('/[\s\n]+/', ' ', $string);
        $string = str_replace(array('array ', "( ", ', )'), array('array', "(", ')'), $string);
        return $string;
    }

    /**
     * Removes entries from given array, that might contain password information, depending
     * on the key name of each entry.
     * Multidimensional arrays are supported.
     *
     * @param array $data Any array potentially containing password information
     * @param bool $useReplacement
     */
    public function removePasswordsFromArray(array &$data, $useReplacement = true)
    {
        // Remove entries containing one of these (or even partially), if...
        $keyNamePartsToRemove = array('pass', 'pw', 'secret', 'hash');

        // ...they do not contain one of those bits (switches from iconfig, that do not really contain passwords)
        $keyNamePartsToIgnore = array('_int#+#]', '_bool#+#]');

        $replacement = 'XXXXXXXX';

        // Go through all elements of the given array
        foreach (array_keys($data) as $key) {
            // Indexes are better compared in lower case
            $keyName = strtolower(trim($key));

            // Go through all index names to remove
            foreach ($keyNamePartsToRemove as $keyNamePartToRemove) {
                // Does the current index (completely or partial) match?
                if ($keyName == $keyNamePartToRemove || strpos($keyName, $keyNamePartToRemove) !== false) {
                    // Skip removing the element for some special cases
                    foreach ($keyNamePartsToIgnore as $keyNamePartToIgnore) {
                        if (strpos($keyName, $keyNamePartToIgnore) !== false) {
                            break 2;
                        }
                    }

                    // Remove the element from the given array
                    if ($useReplacement) {
                        $data[$key] = $replacement;
                    } else {
                        unset($data[$key]);
                    }
                    break;
                }
            }

            // Recursively remove passwords from sub-arrays, too (if element hasn't been unset already)
            if (isset($data[$key]) && is_array($data[$key])) {
                $this->removePasswordsFromArray($data[$key]);
            }
        }
    }

}

//endclass