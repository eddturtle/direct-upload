<?php

namespace EddTurtle\DirectUpload;

/**
 * Class Profile
 *
 * Loads the AWS key and secret for a named profile from the shared credentials file
 * (~/.aws/credentials), the same file the AWS CLI and SDKs use.
 *
 * @package EddTurtle\DirectUpload
 */
class Profile
{
    /**
     * Get the credentials for a profile.
     *
     * @param string $name the profile's name, e.g. "default".
     *
     * @return array with the keys 'key', 'secret' and 'token' (null unless the profile has a session token).
     *
     * @throws \InvalidArgumentException if the file/profile can't be found or isn't usable.
     */
    public static function getCredentials(string $name): array
    {
        if (empty($name)) {
            throw new \InvalidArgumentException("Empty AWS Profile Provided");
        }

        $file = self::getFile();
        if (!is_readable($file)) {
            throw new \InvalidArgumentException("AWS credentials file '$file' could not be read");
        }

        $profiles = self::parse((string)file_get_contents($file));
        if (!isset($profiles[$name])) {
            throw new \InvalidArgumentException("AWS Profile '$name' not found in '$file'");
        }
        $profile = $profiles[$name];

        if (empty($profile['aws_access_key_id']) || empty($profile['aws_secret_access_key'])) {
            throw new \InvalidArgumentException(
                "AWS Profile '$name' must contain aws_access_key_id and aws_secret_access_key"
            );
        }

        return [
            'key' => $profile['aws_access_key_id'],
            'secret' => $profile['aws_secret_access_key'],
            // Only present for temporary credentials (e.g. from SSO or an assumed role).
            'token' => !empty($profile['aws_session_token']) ? $profile['aws_session_token'] : null,
        ];
    }

    private static function getFile(): string
    {
        return rtrim(self::getHomeDirectory(), '/\\') . DIRECTORY_SEPARATOR . '.aws' . DIRECTORY_SEPARATOR . 'credentials';
    }

    /**
     * Web servers (Apache, php-fpm) often don't pass HOME through to PHP, so if the environment doesn't
     * know the home directory, ask the system for the home of the user PHP is running as.
     */
    private static function getHomeDirectory(): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE');

        if (empty($home) && function_exists('posix_getpwuid')) {
            $user = posix_getpwuid(posix_geteuid());
            $home = $user['dir'] ?? '';
        }

        return (string)$home;
    }

    /**
     * Parse the ini style credentials file into [profile => [setting => value]]. This is done by hand
     * as parse_ini_string() can't be relied upon with the '#' comments the AWS tools allow.
     *
     * @param string $contents the file's contents.
     *
     * @return array
     */
    private static function parse(string $contents): array
    {
        $profiles = [];
        $section = null;

        foreach (preg_split('/\r\n|\r|\n/', $contents) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
                $section = trim($matches[1]);
                $profiles[$section] = $profiles[$section] ?? [];
            } elseif ($section !== null && strpos($line, '=') !== false) {
                list($setting, $value) = explode('=', $line, 2);
                $profiles[$section][strtolower(trim($setting))] = trim($value);
            }
        }

        return $profiles;
    }
}
