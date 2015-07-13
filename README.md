# Release calendar for ownCloud

	composer install
	php releases.php
	# use ownCloud-releases.ical

At ownCloud we moved to time based releases and what would be better than
generating the calendar for the release planning (dates of freezes, alphas,
betas, RCs, releases). This script reads the release cycle schema from a
JSON file. It allows to specify a cycle schema for major (version number
ends on `.0`) and minor releases (every other version number). These cycles
are specified in days after the last release (on the version series 6.0.8
is the previous release of 6.0.9).

The `minor` and `major` elements contain key value pairs. The key is the
number of the day after the last release. The value contains an element
named `title` and `comment` which are added as such to the calendar event.

`minor` and `major` also contain a special key value pair with the key
`cycle` which determines the cycle count. This allows to specify a date
after the actual release (e.g. provide security advisories one/two weeks
after the release).

A third element is named `releases`. This holds key value pairs where the
key is the actual version and the value is an array of key value pairs, with
a mapping of a day (the ones that are specified in `minor`/`major`) to a
specific date. For major releases at least the one for day `1` is needed to
base the calculation on. For minor releases this is only needed for the first
in a version series (only for `6.0.8` and `6.0.9`, `6.0.10` are calculated
ontop of this). You can also overwrite special dates with this option. If you
need to shift a date for i.e. the release of the beta1 and then beta1 is on
day 21 of the minor release, you can simply say:

	"21": "2015-07-14"

Then the date of the 21 day will be shifted to that date and all dates afterward
are based on this shift.

### Example

	{
	  "minor": {
		"cycle": "28",
		"14": {
		  "title": "beta",
		  "comment": "beta release"
		},
		"21": {
		  "title": "rc",
		  "comment": "rc release"
		},
		"28": {
		  "title": "Release",
		  "comment": "release is announced"
		},
		"35": {
		  "title": "Security advisories",
		  "comment": "Security advisories are made public"
		}
	  },
	  "major": {
	  },
	  "releases": {
		"6.0.9": {
		  "1": "2015-06-10",
		  "21": "2015-07-05"
		},
		"6.0.10": {}
	  }
	}

This example will generate following dates:

	2015-06-23 : 6.0.9 beta - beta release
	2015-07-05 : 6.0.9 rc - rc release
	2015-07-12 : 6.0.9 Release - release is announced
	2015-07-19 : 6.0.9 Security advisories - Security advisories are made public
	2015-07-26 : 6.0.10 beta - beta release
	2015-08-02 : 6.0.10 rc - rc release
	2015-08-09 : 6.0.10 Release - release is announced
	2015-08-16 : 6.0.10 Security advisories - Security advisories are made public

Without the line `"21": "2015-07-05"` in `releases` -> `6.0.9` it would be not
shifted 5 days:

	2015-06-23 : 6.0.9 beta - beta release
	2015-06-30 : 6.0.9 rc - rc release
	2015-07-07 : 6.0.9 Release - release is announced
	2015-07-14 : 6.0.9 Security advisories - Security advisories are made public
	2015-07-21 : 6.0.10 beta - beta release
	2015-07-28 : 6.0.10 rc - rc release
	2015-08-04 : 6.0.10 Release - release is announced
	2015-08-11 : 6.0.10 Security advisories - Security advisories are made public


Also the beta of `6.0.10` is 14 days after the release of `6.0.9` and not 14 days after
the security advisories announcement.

## License 

The MIT License (MIT)

Copyright (c) 2015 Morris Jobke

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
