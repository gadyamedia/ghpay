# Application → Candidate Flow & Typing Test Tracking

## How It Works

### 1. **Application Submission** (Public Form)
When someone applies through `/careers/{position}/apply`:

```php
// resources/views/livewire/careers/apply.blade.php

// Step 1: Create the application record
$application = Application::create([
    'position_id' => $this->position->id,
    'first_name' => $this->first_name,
    'last_name' => $this->last_name,
    'email' => $this->email,
    'phone' => $this->phone,
    'resume_path' => $resumePath,
    'screening_answers' => $this->screening_answers,
    'status' => 'new',
]);

// Step 2: Auto-create or find existing candidate
$candidate = Candidate::firstOrCreate(
    ['email' => $this->email],  // Find by email
    [
        'name' => $this->first_name . ' ' . $this->last_name,
        'phone' => $this->phone,
    ]
);

// Step 3: Link application to candidate
$application->update(['candidate_id' => $candidate->id]);

// Step 4: Auto-send typing test if enabled
if ($this->position->auto_send_typing_test && $this->position->require_typing_test) {
    $invitation = TestInvitation::createForCandidate($candidate, $textSampleId);
    Mail::to($candidate->email)->send(new TestInvitationMail($invitation));
    $application->update(['status' => 'typing_test_sent']);
}
```

### 2. **Database Relationships**

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│   Application   │──────>│    Candidate    │──────>│  TypingTest     │
│                 │       │                 │       │                 │
│ - first_name    │       │ - name          │       │ - wpm           │
│ - last_name     │       │ - email         │       │ - accuracy      │
│ - email         │       │ - phone         │       │ - duration      │
│ - resume_path   │       │ - status        │       │ - completed_at  │
│ - candidate_id──┼──────>│                 │       │                 │
│ - status        │       │                 │       │                 │
└─────────────────┘       └─────────────────┘       └─────────────────┘
                                   │
                                   │
                                   v
                          ┌─────────────────┐
                          │ TestInvitation  │
                          │                 │
                          │ - token         │
                          │ - expires_at    │
                          │ - opened_at     │
                          │ - completed_at  │
                          └─────────────────┘
```

**Key Points:**
- One **Candidate** can have multiple **Applications** (applying for different positions)
- One **Candidate** can have multiple **TestInvitations** (can be invited multiple times)
- One **Candidate** can have multiple **TypingTests** (can take test multiple times)
- **Application** stores the original application data (first name, last name separately)
- **Candidate** stores the consolidated profile (full name, best performance)

### 3. **Admin Interface - Applications View**

**Location:** `/admin/applications`

**Shows:**
- List of all applications with filters (position, status, search)
- Application details: applicant info, resume, screening answers
- Candidate link (if candidate exists)
- Status tracking with these stages:
  - `new` - Just submitted
  - `reviewed` - Admin has reviewed
  - `typing_test_sent` - Invitation sent (🟡 badge)
  - `typing_test_completed` - Test finished (🟢 badge)
  - `interview` - Scheduled for interview
  - `offer` - Offer extended
  - `hired` - Accepted
  - `rejected` - Declined

**Key Features:**
```php
// View application details including candidate info
$application = Application::with(['candidate', 'position'])->find($id);

// Access linked candidate's typing test results
$typingTests = $application->candidate?->typingTests;
$bestWpm = $application->candidate?->bestWpm;
```

### 4. **Admin Interface - Candidates View**

**Location:** `/admin/candidates`

**Shows:**
- All candidates (consolidated by email)
- Latest typing test performance
- Test invitation status
- Number of tests taken

**Candidate Manager Features:**
```blade
@foreach ($candidates as $candidate)
    <tr>
        <td>{{ $candidate->name }}</td>
        <td>{{ $candidate->email }}</td>
        <td>{{ $candidate->position_applied }}</td>
        <td>
            @if($candidate->latestTypingTest)
                <span class="font-bold">{{ $candidate->latestTypingTest->wpm }} WPM</span>
                <span class="text-sm">({{ number_format($candidate->latestTypingTest->accuracy, 1) }}%)</span>
            @else
                <span class="text-gray-400">No test yet</span>
            @endif
        </td>
        <td>
            @if($candidate->activeInvitation)
                <span class="badge badge-warning">Invited</span>
                <span class="text-xs">Expires: {{ $candidate->activeInvitation->expires_at->format('M d') }}</span>
            @endif
        </td>
    </tr>
@endforeach
```

### 5. **Candidate Results Page**

**Location:** `/admin/candidates/{candidateId}`

**Shows detailed performance metrics:**

```blade
<!-- Performance Summary Cards -->
<div class="grid grid-cols-4 gap-6">
    <div class="bg-blue-50 p-4">
        <div class="text-3xl font-bold text-blue-600">{{ $candidate->bestWpm }}</div>
        <div class="text-sm">Best WPM</div>
    </div>
    <div class="bg-green-50 p-4">
        <div class="text-3xl font-bold text-green-600">{{ number_format($candidate->averageAccuracy, 1) }}%</div>
        <div class="text-sm">Avg Accuracy</div>
    </div>
    <div class="bg-purple-50 p-4">
        <div class="text-3xl font-bold text-purple-600">{{ $candidate->typingTests->count() }}</div>
        <div class="text-sm">Tests Taken</div>
    </div>
    <div class="bg-orange-50 p-4">
        <div class="text-3xl font-bold text-orange-600">{{ number_format($candidate->typingTests->avg('duration_seconds')) }}s</div>
        <div class="text-sm">Avg Duration</div>
    </div>
</div>

<!-- All Test Results Table -->
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Test Name</th>
            <th>WPM</th>
            <th>Accuracy</th>
            <th>Duration</th>
            <th>Difficulty</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($candidate->typingTests as $test)
            <tr>
                <td>{{ $test->completed_at->format('M d, Y g:i A') }}</td>
                <td>{{ $test->typingTextSample->title }}</td>
                <td class="text-blue-600 font-bold">{{ $test->wpm }}</td>
                <td class="text-green-600 font-bold">{{ number_format($test->accuracy, 1) }}%</td>
                <td>{{ $test->duration_seconds }}s</td>
                <td>{{ $test->typingTextSample->difficulty }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
```

## Tracking Typing Test Progress

### Candidate Model Methods

```php
// Get best WPM across all tests
$candidate->bestWpm; // Uses accessor

// Get average accuracy
$candidate->averageAccuracy; // Uses accessor

// Get latest test
$candidate->latestTypingTest; // HasOne relationship

// Check if has active invitation
$candidate->activeInvitation; // HasOne relationship (not expired, not completed)

// Get all typing tests
$candidate->typingTests; // HasMany relationship

// Get all test invitations
$candidate->testInvitations; // HasMany relationship
```

### Application Statuses Related to Typing Test

1. **`new`** - Application just submitted, no typing test action yet
2. **`typing_test_sent`** - Email with test link sent to candidate
3. **`typing_test_completed`** - Candidate completed the test
4. **`reviewed`** - Admin reviewed application and test results
5. **`interview`** / **`offer`** / **`hired`** - Next stages

### Email Flow

```
1. Application Submitted
   └─> ApplicationReceived email sent to admin
   
2. If position.auto_send_typing_test = true
   └─> TestInvitationMail sent to candidate
       └─> Contains unique token link
       └─> Link expires in 72 hours (default)
   
3. Candidate clicks link & completes test
   └─> Status changes to "typing_test_completed"
   └─> Admin can view results in:
       - /admin/applications (see status)
       - /admin/candidates (see performance)
       - /admin/candidates/{id} (see detailed results)
       
4. Admin changes application status
   └─> ApplicationStatusUpdated email sent
       └─> Different messages for: interview, offer, hired, rejected
```

## Example Use Cases

### Use Case 1: Track Applicant's Typing Test
```php
// From application record
$application = Application::find(1);

// Check if typing test was sent
$status = $application->status; // 'typing_test_sent'

// View candidate's test results
$candidate = $application->candidate;
$tests = $candidate->typingTests; // Collection of all tests
$bestWpm = $candidate->bestWpm; // Best WPM score
```

### Use Case 2: Find All Candidates Who Haven't Completed Test
```php
$candidates = Candidate::whereHas('testInvitations', function($query) {
    $query->whereNull('completed_at')
          ->where('expires_at', '>', now());
})->get();
```

### Use Case 3: Resend Typing Test Invitation
```php
// Admin can resend from Candidate Manager page
public function sendTestInvitation(int $candidateId, int $textSampleId): void
{
    $candidate = Candidate::findOrFail($candidateId);
    $invitation = TestInvitation::createForCandidate($candidate, $textSampleId);
    Mail::to($candidate->email)->send(new TestInvitationMail($invitation));
}
```

### Use Case 4: Bulk Update Application Status
```php
// From Applications page
public function bulkUpdateStatus(string $newStatus): void
{
    foreach ($this->selectedApplications as $id) {
        $application = Application::find($id);
        $application->updateStatus($newStatus);
        
        // Send appropriate email
        Mail::to($application->email)->send(
            new ApplicationStatusUpdated($application)
        );
    }
}
```

## Navigation Between Interfaces

```
/admin/applications (List all applications)
  └─> Click on application
      └─> View modal with:
          - Applicant info
          - Resume download
          - Screening answers
          - Admin notes
          - [View Candidate Profile] button
              └─> /admin/candidates/{candidateId}
                  └─> Performance summary
                  └─> All test results
                  └─> Test invitations history

/admin/candidates (List all candidates)
  └─> Click on candidate
      └─> /admin/candidates/{candidateId}
          └─> View all typing tests
          └─> Performance metrics
          └─> [Send Test Invitation] button
```

## Key Advantages of This Architecture

1. **No Duplicate Candidates** - Email is unique identifier
2. **Historical Tracking** - See all applications from same person
3. **Performance Analytics** - Track improvement over multiple tests
4. **Flexible Workflow** - Can invite for typing test at any time
5. **Status Automation** - Status updates trigger appropriate emails
6. **Consolidated View** - Admin sees complete candidate profile

## Important Files

- **Models:**
  - `app/Models/Application.php` - Application record with status tracking
  - `app/Models/Candidate.php` - Candidate profile with performance metrics
  - `app/Models/TestInvitation.php` - Typing test invitation with token
  - `app/Models/TypingTest.php` - Completed test results

- **Admin Interfaces:**
  - `resources/views/livewire/admin/applications/index.blade.php` - Applications list
  - `resources/views/livewire/admin/candidate-manager.blade.php` - Candidates list
  - `resources/views/livewire/admin/candidate-results.blade.php` - Detailed test results

- **Public Interface:**
  - `resources/views/livewire/careers/apply.blade.php` - Application form

- **Emails:**
  - `app/Mail/TestInvitationMail.php` - Typing test invitation
  - `app/Mail/ApplicationStatusUpdated.php` - Status change notification
