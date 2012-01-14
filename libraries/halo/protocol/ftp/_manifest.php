<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\ftp;

use df;
use df\core;
use df\halo;

// Interfaces
interface IUrl extends core\uri\IGenericUrl, core\uri\ICredentialContainer, core\uri\ISecureSchemeContainer, core\uri\IDomainPortContainer {}