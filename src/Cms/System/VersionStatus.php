<?php

namespace Kirby\Cms\System;


enum VersionStatus: string
{
	case Latest = 'latest';
	case NoVulnerabilities = 'no-vulnerabilities';
	case Unreleased = 'unreleased';
	case EndOfLife = 'end-of-life';
}
