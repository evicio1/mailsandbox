<?php

namespace App\Services;

class OtpDetectorService
{
    public function extractBestOtp(?string $text): ?string
    {
        if (empty($text)) {
            return null;
        }

        $keywords = ['otp', 'verification', 'code', 'one-time', 'passcode', 'pin'];
        
        // Find all 4 to 8 digit numbers surrounded by boundaries
        if (preg_match_all('/\b(\d{4,8})\b/', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $candidates = [];
            $textLower = strtolower($text);

            foreach ($matches[1] as $match) {
                $otp = $match[0];
                $offset = $match[1];
                
                // Extract a window of text around the OTP to check for keywords
                $start = max(0, $offset - 100);
                $window = substr($textLower, $start, 200);
                
                $score = 0;
                foreach ($keywords as $kw) {
                    if (strpos($window, $kw) !== false) {
                        $score += 10;
                    }
                }
                
                // Prefer 6-digit as standard
                if (strlen($otp) === 6) {
                    $score += 5;
                }

                $candidates[] = [
                    'otp' => $otp,
                    'score' => $score,
                    'position' => $offset
                ];
            }

            if (!empty($candidates)) {
                // Sort by score DESC, then by position (first appearance)
                usort($candidates, function($a, $b) {
                    if ($a['score'] === $b['score']) {
                        return $a['position'] <=> $b['position'];
                    }
                    return $b['score'] <=> $a['score'];
                });

                // Return highest scored OTP if score > 0, otherwise just the first found
                if ($candidates[0]['score'] > 0) {
                    return $candidates[0]['otp'];
                }
                return $candidates[0]['otp'];
            }
        }

        return null;
    }
}
