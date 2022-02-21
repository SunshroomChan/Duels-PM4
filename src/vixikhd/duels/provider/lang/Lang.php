<?php

/**
 * Copyright 2018 GamakCZ
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace vixikhd\duels\provider\lang;

/**
 * Class Lang
 * @package duels\lang
 */
class Lang {

    /** @var array $messages */
    private static $messages = [];

    /**
     * Lang constructor.
     * @param array $messages
     */
    public function __construct(array $messages) {
        self::$messages = $messages;
    }

    /**
     * @param string $index
     * @param array $params
     * @return string
     */
    public static function getMsg(string $index, array $params = []): string {
        if(!isset(self::$messages[$index])) {
            return $index;
        }

        if(empty($params)) {
            return self::$messages[$index];
        }

        $msg = self::$messages[$index];
        foreach ($params as $index => $param) {
            $msg = str_replace("{%{$index}}", (string) $param, $msg);
        }

        $msg = str_replace("{%line}", "\n", $msg);

        return $msg;
    }

    /**
     * @param string $index
     * @return bool
     */
    public static function canSend(string $index): bool {
        return isset(self::$messages[$index]) && self::$messages[$index] != "";
    }
}