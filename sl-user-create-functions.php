<?php
/**
 * Copyright 2011-2023 by Gwyneth Llewelyn. All rights reserved.
 *
 * Released under a BSD-3-clause license.
 *
 *
 * Based on my own code for Online Status inSL, https://www.wordpress.org/plugins/online-status-insl/
 *
 **/

// Common functions

/*
 * Deal with avatars called "SomethingOrOther Resident"
 *  and sanitise the name by replacing spaces with underscores (bug reported by @slprof)
 */
if (!function_exists("sanitise_avatarname"))
{
	function sanitise_avatarname($avatarName, $dotOrSlash = ".")
	{
		$sanitised = rawurlencode(strtolower(strtr($avatarName, " ", $dotOrSlash)));
		// check if 'Resident' is appended
		if (($match = stripos($sanitised, 'Resident')) !== FALSE)
		{
			// return everything up to the character before the dot
			return substr($sanitised, 0, $match - 1);
		}
		else
		{
			return $sanitised;
		}
	}
}
?>