<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the device information in a readable format
     */
    public function getDeviceInfoAttribute()
    {
        $info = [];
        
        if ($this->name) {
            $info['device'] = $this->name;
        }
        
        if ($this->ip_address) {
            $info['ip'] = $this->ip_address;
        }
        
        if ($this->user_agent) {
            $info['browser'] = $this->parseBrowser($this->user_agent);
            $info['os'] = $this->parseOS($this->user_agent);
        }
        
        return $info;
    }

    /**
     * Parse browser from user agent
     */
    private function parseBrowser($userAgent)
    {
        if (preg_match('/Chrome\/[\d\.]+/', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Firefox\/[\d\.]+/', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Safari\/[\d\.]+/', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Edge\/[\d\.]+/', $userAgent)) {
            return 'Edge';
        }
        
        return 'Unknown';
    }

    /**
     * Parse OS from user agent
     */
    private function parseOS($userAgent)
    {
        if (preg_match('/Windows NT [\d\.]+/', $userAgent)) {
            return 'Windows';
        } elseif (preg_match('/Mac OS X [\d_\.]+/', $userAgent)) {
            return 'macOS';
        } elseif (preg_match('/iPhone OS [\d_\.]+/', $userAgent)) {
            return 'iOS';
        } elseif (preg_match('/Android [\d\.]+/', $userAgent)) {
            return 'Android';
        } elseif (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }
        
        return 'Unknown';
    }
}