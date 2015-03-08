<?php

/**
 * Copyright (c) 2015-present Stuart Herbert.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package     Stuart
 * @subpackage  SemverLib
 * @author      Stuart Herbert <stuart@stuartherbert.com>
 * @copyright   2015-present Stuart Herbert
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://stuartherbert.github.io/php-semver
 */

namespace Stuart\SemverLib;

/**
 * Compares two versions
 */
class VersionComparitor
{
	/**
	 * returned from self::compare() when $a is the smaller version
	 */
	const A_IS_LESS = -1;

	/**
	 * returned from self::compare() when $a and $b are the same version
	 */
	const BOTH_ARE_EQUAL = 0;

	/**
	 * returned from self::compare() when $a is the larger version
	 */
	const A_IS_GREATER = 1;

	/**
	 * constructor
	 *
	 * added for PHPUnit code coverage purposes
	 */
	public function __construct()
	{
		// do nothing
	}

	/**
	 * compare two semantic version numbers
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return int
	 *         one of the self::* consts
	 */
	public function compare(SemanticVersion $a, SemanticVersion $b)
	{
		// save us some processing time
		$aVer = $a->__toArray();
		$bVer = $b->__toArray();

		// compare major.minor.patchLevel first
		$retval = $this->compareXYZ($aVer, $bVer);
		if ($retval != self::BOTH_ARE_EQUAL) {
			return $retval;
		}

		// are there any pre-release strings to compare?
		if (!isset($aVer['preRelease']) && !isset($bVer['preRelease'])) {
			return $retval;
		}

		// do we only have one pre-release string?
		if (isset($aVer['preRelease']) && !isset($bVer['preRelease'])) {
			return self::A_IS_LESS;
		}
		else if (!isset($aVer['preRelease']) && isset($bVer['preRelease'])) {
			return self::A_IS_GREATER;
		}

		// if we get here, we need to get into comparing the pre-release
		// strings
		return $this->comparePreRelease($aVer['preRelease'], $bVer['preRelease']);
	}

	/**
	 * compare the X.Y.Z parts of two version numbers
	 *
	 * @param  array $aVer
	 * @param  array $bVer
	 * @return int
	 *         -1 if $aVer is smaller
	 *          0 if both are equal
	 *          1 if $aVer is larger
	 */
	public function compareXYZ($aVer, $bVer)
	{
		// compare major version numbers
		if ($aVer['major'] < $bVer['major']) {
			return self::A_IS_LESS;
		}
		else if ($aVer['major'] > $bVer['major']) {
			return self::A_IS_GREATER;
		}

		// compare minor version numbers
		if ($aVer['minor'] < $bVer['minor']) {
			return self::A_IS_LESS;
		}
		else if ($aVer['minor'] > $bVer['minor']) {
			return self::A_IS_GREATER;
		}

		// what about the patch level?
		//
		// this is optional; we infer a value of '0' when none is supplied
		if (!isset($aVer['patchLevel'])) {
			$aPatchLevel = 0;
		}
		else {
			$aPatchLevel = $aVer['patchLevel'];
		}
		if (!isset($bVer['patchLevel'])) {
			$bPatchLevel = 0;
		}
		else {
			$bPatchLevel = $bVer['patchLevel'];
		}
		if ($aPatchLevel < $bPatchLevel) {
			return self::A_IS_LESS;
		}
		else if ($aPatchLevel > $bPatchLevel) {
			return self::A_IS_GREATER;
		}

		return self::BOTH_ARE_EQUAL;
	}

	/**
	 * compare two pre-release strings
	 *
	 * @param  string $a
	 * @param  string $b
	 * @return int
	 *         -1 if $a is smaller
	 *          0 if both are the same
	 *          1 if $a is larger
	 */
	public function comparePreRelease($a, $b)
	{
		// according to semver.org, dots are the delimiters to the parts
		// of the pre-release strings
		$aParts = explode(".", $a);
		$bParts = explode(".", $b);

		// step-by-step comparison
		foreach ($aParts as $i => $aPart)
		{
			// if we've run out of parts, $a wins
			if (!isset($bParts[$i])) {
				return self::A_IS_GREATER;
			}

			// shorthand
			$bPart = $bParts[$i];

			// what are we looking at?
			$aPartIsNumeric = ctype_digit($aPart);
			$bPartIsNumeric = ctype_digit($bPart);

			// make sense of it
			if ($aPartIsNumeric) {
				if (!$bPartIsNumeric) {
					// $bPart is a string
					//
					// strings always win
					return self::A_IS_LESS;
				}

				// at this point, we have two numbers
				$aInt = strval($aPart);
				$bInt = strval($bPart);

				if ($aInt < $bInt) {
					return self::A_IS_LESS;
				}
				else if ($aInt > $bInt) {
					return self::A_IS_GREATER;
				}
			}
			else if ($bPartIsNumeric) {
				// $aPart is a string
				//
				// strings always win
				return self::A_IS_GREATER;
			}
			else {
				// two strings to compare
				//
				// unfortunately, strcmp() doesn't return -1 / 0 / 1
				$res = strcmp($aPart, $bPart);
				if ($res < 0) {
					return self::A_IS_LESS;
				}
				else if ($res > 0) {
					return self::A_IS_GREATER;
				}
			}
		}

		// does $b have any more parts?
		if (count($aParts) < count($bParts)) {
			return self::A_IS_LESS;
		}

		// at this point, we've exhausted all of the possibilities
		return self::BOTH_ARE_EQUAL;
	}

	/**
	 * are two version numbers the same?
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 *         TRUE if they are the same
	 *         FALSE otherwise
	 */
	public function equals(SemanticVersion $a, SemanticVersion $b)
	{
		$res = $this->compare($a, $b);
		if ($res == 0) {
			return true;
		}

		return false;
	}

	/**
	 * is version number $b greater than $a?
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 *         TRUE if $b is greater than $a
	 *         FALSE otherwise
	 */
	public function isGreaterThan(SemanticVersion $a, SemanticVersion $b)
	{
		$res = $this->compare($a, $b);
		if ($res >= 0) {
			return false;
		}

		return true;
	}

	/**
	 * is version number $b >= $a?
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 *         TRUE if $b >= $a
	 *         FALSE otherwise
	 */
	public function isGreaterThanOrEqualTo(SemanticVersion $a, SemanticVersion $b)
	{
		$res = $this->compare($a, $b);
		if ($res > 0) {
			return false;
		}

		return true;
	}

	/**
	 * is version number $b <= $a?
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 *         TRUE if $b <= $a
	 *         FALSE otherwise
	 */
	public function isLessThanOrEqualTo(SemanticVersion $a, SemanticVersion $b)
	{
		$res = $this->compare($a, $b);
		if ($res < 0) {
			return false;
		}

		return true;
	}

	/**
	 * is version number $b < $a?
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 *         TRUE if $b < $a
	 *         FALSE otherwise
	 */
	public function isLessThan(SemanticVersion $a, SemanticVersion $b)
	{
		$res = $this->compare($a, $b);
		if ($res > 0) {
			return true;
		}

		return false;
	}

	/**
	 * is version number $b something that we should avoid using, according
	 * to version number $a?
	 *
	 * (this is the !$a expression)
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 *         TRUE if $b should be avoided
	 *         FALSE otherwise
	 */
	public function avoid(SemanticVersion $a, SemanticVersion $b)
	{
		$res = $this->compare($a, $b);
		if ($res == 0) {
			return false;
		}

		return true;
	}

	/**
	 * is version number $b approximately the same as $a?
	 *
	 * (this is the ~$a expression)
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 */
	public function isApproximately(SemanticVersion $a, SemanticVersion $b)
	{
		// we turn this into two tests:
		//
		// $b has to be >= $a, and
		// $b has to be < $c
		//
		// where $c is our calculated upper bound for the proximity operator
		$res = $this->isGreaterThanOrEqualTo($a, $b);
		if (!$res) {
			return false;
		}

		// work out our upper boundary
		//
		// ~1.2.3 becomes <1.3.0
		// ~1.2   becomes <2.0.0
		//
		// and keep track of which boundary we're locked against
		$boundByMajor = false;
		$boundByMinor = false;

		$c = new SemanticVersion();
		if ($a->getPatchLevel() > 0) {
			$upperBound = $a->getMajor() . '.' . ($a->getMinor() + 1);
			$boundByMinor = true;
		}
		else {
			$upperBound = ($a->getMajor() + 1) . '.0';
			$boundByMajor = true;
		}
		$c->setVersion($upperBound);

		$res = $this->isLessThan($c, $b);
		if (!$res) {
			return false;
		}

		// finally, a special case
		// avoid installing an unstable version of the upper boundary
		if ($c->getMajor() == $b->getMajor() && $b->getPreRelease() !== null) {
			return false;
		}
		if ($boundByMinor && $c->getMinor() == $b->getMinor() && $b->getPreRelease() !== null) {
			return false;
		}

		// if we get here, then we're good
		return true;
	}

	/**
	 * is version number $b compatible with $a?
	 *
	 * (this is the ^$a expression)
	 *
	 * @param  SemanticVersion $a
	 * @param  SemanticVersion $b
	 * @return boolean
	 *         TRUE if $b is compatible with $a
	 *         FALSE otherwise
	 */
	public function isCompatible(SemanticVersion $a, SemanticVersion $b)
	{
		// we turn this into two tests:
		//
		// $b has to be >= $a, and
		// $b has to be < $c
		//
		// where $c is our next stable major version
		$res = $this->isGreaterThanOrEqualTo($a, $b);
		if (!$res) {
			return false;
		}

		// calculate our upper boundary
		$c = new SemanticVersion();
		$c->setVersion($a->getMajor() +1 . '.0');

		$res = $this->isLessThan($c, $b);
		if (!$res) {
			return false;
		}

		// finally, a special case
		// avoid installing an unstable version of the upper boundary
		if ($c->getMajor() == $b->getMajor() && $b->getPreRelease() !== null) {
			return false;
		}

		// if we get here, we're good
		return true;
	}

	/**
	 * are hashes $a and $b the same?
	 *
	 * (this is the @$a operator for pinning to a Git commit)
	 *
	 * @param  string $a
	 *         a hash string
	 * @param  string $b
	 *         the hash string to compare against $a
	 * @return boolean
	 *         TRUE if $a == $b
	 */
	public function equalNonVersion($a, $b)
	{
		if (strcmp($a, $b) == 0) {
			return true;
		}

		return false;
	}
}