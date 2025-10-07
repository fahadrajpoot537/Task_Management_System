<?php

namespace App\Services;

class PasswordGeneratorService
{
    /**
     * Generate a secure temporary password
     * Format: Number + Special Char + Letters + Special Char + Letters
     * Example: 3(La!ddh
     */
    public static function generateTempPassword(): string
    {
        // Define character sets
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        // Build password components
        $password = '';
        
        // Start with a number (1-9)
        $password .= rand(1, 9);
        
        // Add a special character
        $password .= $specialChars[rand(0, strlen($specialChars) - 1)];
        
        // Add 2-3 lowercase letters
        $lowerCount = rand(2, 3);
        for ($i = 0; $i < $lowerCount; $i++) {
            $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        }
        
        // Add a special character
        $password .= $specialChars[rand(0, strlen($specialChars) - 1)];
        
        // Add 2-3 lowercase letters
        $lowerCount = rand(2, 3);
        for ($i = 0; $i < $lowerCount; $i++) {
            $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Generate multiple password options for selection
     */
    public static function generatePasswordOptions(int $count = 3): array
    {
        $passwords = [];
        for ($i = 0; $i < $count; $i++) {
            $passwords[] = self::generateTempPassword();
        }
        return $passwords;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePasswordStrength(string $password): bool
    {
        // Check minimum length
        if (strlen($password) < 6) {
            return false;
        }
        
        // Check for at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Check for at least one special character
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $password)) {
            return false;
        }
        
        // Check for at least one letter
        if (!preg_match('/[a-zA-Z]/', $password)) {
            return false;
        }
        
        return true;
    }
}
