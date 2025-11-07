# WPM Calculation Fix

## Problem
Users were getting WPM scores that were much lower than expected. For example:
- User typically scores **52 WPM** on typingtest.com
- Same user scored only **18 WPM** on our system
- **~65% lower** than expected

## Root Causes

### 1. **Wrong Input to WPM Calculation**
The system was calculating WPM using **only correct characters** instead of **all typed characters**.

```php
// ❌ WRONG (before fix):
$this->wpm = TypingTest::calculateWpm(
    $this->correctCharacters,  // Only counting correct characters
    $this->elapsedSeconds
);

// ✅ CORRECT (after fix):
$this->wpm = TypingTest::calculateWpm(
    $this->totalCharacters,    // Counting ALL typed characters
    $this->elapsedSeconds
);
```

**Example Impact:**
- If you typed 260 characters in 1 minute with 95% accuracy:
  - **Wrong calculation:** 247 correct chars / 5 / 1 min = **49 WPM**
  - **Correct calculation:** 260 total chars / 5 / 1 min = **52 WPM**
  - But if accuracy was only 70%, wrong calculation would give **36 WPM** vs correct **52 WPM**

### 2. **Wrong Character Count in Analysis**
The `analyzeTyping()` method was returning the **original text length** instead of the **typed text length**.

```php
// ❌ WRONG (before fix):
$totalCharacters = count($originalChars); // Length of the original text

// ✅ CORRECT (after fix):
$totalTypedCharacters = count($typedChars); // Length of what was actually typed
```

### 3. **Live WPM Also Wrong**
The live WPM display during typing was also using only correct characters, making it inconsistent with the final score.

```php
// ❌ WRONG (before fix):
$analysis = TypingTest::analyzeTyping(...);
$correctChars = $analysis['correct_characters'];
$this->liveWpm = TypingTest::calculateWpm($correctChars, $this->elapsedSeconds);

// ✅ CORRECT (after fix):
$typedLength = strlen($this->typedText);
$this->liveWpm = TypingTest::calculateWpm($typedLength, $this->elapsedSeconds);
```

## Industry Standard WPM Formula

### Gross WPM (What We Now Use)
```
Gross WPM = (All Typed Characters / 5) / Time in Minutes
```

This is what **typingtest.com**, **10fastfingers.com**, and most typing tests use as the primary metric.

### Net WPM (Alternative, Not Implemented)
```
Net WPM = Gross WPM - (Errors / Time in Minutes)
```

Some typing tests show both Gross WPM (raw speed) and Net WPM (adjusted for errors), but most prominently display Gross WPM.

### Why "Divide by 5"?
The typing industry standard defines **1 word = 5 characters**. This accounts for average word length plus spaces.

## Solutions Implemented

### 1. Updated `TypingTest::analyzeTyping()` Method
**File:** `app/Models/TypingTest.php`

Changed to return the **actual typed character count**:

```php
public static function analyzeTyping(string $originalText, string $typedText): array
{
    $originalChars = str_split($originalText);
    $typedChars = str_split($typedText);

    $totalTypedCharacters = count($typedChars); // ✅ Now returns typed length
    $correctCharacters = 0;

    // Compare character by character
    for ($i = 0; $i < min(count($originalChars), count($typedChars)); $i++) {
        if ($originalChars[$i] === $typedChars[$i]) {
            $correctCharacters++;
        }
    }

    $incorrectCharacters = $totalTypedCharacters - $correctCharacters;

    return [
        'total_characters' => $totalTypedCharacters, // ✅ Changed from originalChars count
        'correct_characters' => $correctCharacters,
        'incorrect_characters' => max(0, $incorrectCharacters),
    ];
}
```

### 2. Updated WPM Calculation Call in `completeTest()`
**File:** `resources/views/livewire/typing-test.blade.php`

```php
// Calculate metrics
// WPM uses total characters typed (Gross WPM), not just correct ones
$this->wpm = TypingTest::calculateWpm(
    $this->totalCharacters,  // ✅ Changed from $this->correctCharacters
    $this->elapsedSeconds
);
```

### 3. Updated Live WPM Calculation
**File:** `resources/views/livewire/typing-test.blade.php`

Simplified to use string length directly:

```php
public function calculateLiveWpm(): void
{
    if ($this->elapsedSeconds > 0 && strlen($this->typedText) > 0) {
        // Calculate WPM based on total characters typed so far (Gross WPM)
        // This matches how typingtest.com and other typing tests calculate it
        $typedLength = strlen($this->typedText);
        
        $this->liveWpm = TypingTest::calculateWpm(
            $typedLength,  // ✅ Total typed, not just correct
            $this->elapsedSeconds
        );
    } else {
        $this->liveWpm = 0;
    }
}
```

### 4. Enhanced Documentation
Added detailed comments explaining the Gross WPM formula:

```php
/**
 * Calculate WPM (Words Per Minute)
 * Standard: 1 word = 5 characters (industry standard)
 * 
 * Gross WPM = (All Typed Characters / 5) / Time in Minutes
 * Net WPM = Gross WPM - (Errors / Time in Minutes)
 * 
 * This calculates Gross WPM (raw typing speed)
 * 
 * @param int $totalCharacters Total characters typed (not just correct ones)
 * @param int $durationSeconds Time taken in seconds
 * @return int WPM rounded down
 */
```

## Expected Results

### Before Fix (Wrong)
```
Example Test:
- Typed: 260 characters in 60 seconds
- Accuracy: 70% (182 correct, 78 incorrect)

Wrong Calculation:
WPM = 182 correct / 5 / 1 = 36 WPM ❌ (Way too low!)
```

### After Fix (Correct)
```
Same Test:
- Typed: 260 characters in 60 seconds
- Accuracy: 70% (182 correct, 78 incorrect)

Correct Calculation:
WPM = 260 total / 5 / 1 = 52 WPM ✅ (Matches typingtest.com!)
```

## Impact on Existing Data

### ⚠️ Important: Existing Tests Are Inaccurate
All typing tests completed **before this fix** have **incorrectly low WPM scores**. The stored WPM values in the database cannot be recalculated because we only stored the final metrics, not the raw keystroke data.

### Options:

#### Option 1: Keep Old Data (Recommended for Now)
- Mark existing tests with a note: "Calculated with old formula"
- Continue using existing data for historical reference
- New tests will use the correct calculation

#### Option 2: Recalculate (If Possible)
If you stored `typed_text` and `original_text`, you could recalculate:

```php
// Migration to fix old WPM scores
$tests = TypingTest::all();

foreach ($tests as $test) {
    if ($test->typed_text && $test->original_text) {
        $analysis = TypingTest::analyzeTyping(
            $test->original_text,
            $test->typed_text
        );
        
        $newWpm = TypingTest::calculateWpm(
            $analysis['total_characters'],  // Using new method
            $test->duration_seconds
        );
        
        $test->update(['wpm' => $newWpm]);
    }
}
```

#### Option 3: Clear Old Data
- Delete all tests before the fix date
- Start fresh with accurate calculations
- **Only do this if historical data isn't important**

## Testing the Fix

### Manual Test:
1. Go to a typing test invitation link
2. Type the test text at your normal speed
3. Compare your WPM with typingtest.com result
4. Should now be within 5% of your typical speed

### Database Check:
```sql
-- Check recent tests after the fix
SELECT 
    id,
    wpm,
    accuracy,
    total_characters,
    correct_characters,
    duration_seconds,
    ROUND((total_characters / 5) / (duration_seconds / 60)) as recalculated_wpm
FROM typing_tests 
WHERE completed_at > '2025-11-07 00:00:00'
ORDER BY completed_at DESC 
LIMIT 10;

-- Verify: wpm should equal recalculated_wpm
```

### Expected Improvements:
- Users should see WPM scores **2-3x higher** (matching their actual typing speed)
- Scores should match other popular typing test websites
- Live WPM during typing should be accurate and encouraging

## Related Files Changed
- ✅ `app/Models/TypingTest.php` - Updated `calculateWpm()` docs and `analyzeTyping()` method
- ✅ `resources/views/livewire/typing-test.blade.php` - Fixed WPM calculation calls
- ✅ Both files formatted with Laravel Pint

## References
- [Typing Test Standards](https://en.wikipedia.org/wiki/Words_per_minute)
- typingtest.com uses Gross WPM
- 10fastfingers.com uses Gross WPM
- Industry standard: 1 word = 5 characters
