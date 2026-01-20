<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Messages;

interface BodyAwareMessageInterface
{
    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody(string $body);

    public function getBody(): string;
}
