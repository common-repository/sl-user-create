<?php
/*
Copyright 2011-2023 Gwyneth Llewelyn. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY GWYNETH LLEWELYN ``AS IS'' AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL GWYNETH LLEWELYN OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of Gwyneth Llewelyn.

---

Based on my own code for Online Status inSL, http://wordpress.org/extend/plugins/online-status-insl/

*/

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