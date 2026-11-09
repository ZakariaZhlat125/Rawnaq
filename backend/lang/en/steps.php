<?php

return [
    'step1' => [
        'title' => 'Choose Branch',
        'desc'  => 'Select the nearest branch to view available services.',
        'tips'  => [
            'Services may vary depending on the selected branch',
        ],
    ],

    'step2' => [
        'title' => 'Choose Service',
        'desc'  => 'Select one or more services to add to your booking.',
        'tips'  => [
            'Select your services and click Next',
            'You can return to the previous step to add more services by clicking Previous'
        ],
        'video' => 'videos/steps/service.mp4',
    ],

    'step3' => [
        'title' => 'Choose Service Provider',
        'desc'  => 'Select your preferred staff member.',
        'tips'  => [
            'To choose a service provider, click on the service card to view available staff. Then select your preferred staff member and click Next',
        ],
        'video' => 'videos/steps/staff.mp4',
    ],

    'step4' => [
        'title' => 'Date & Time',
        'desc'  => 'Select the available date and time that suits you.',
        'tips'  => [
            'Click on the service card, choose your preferred date, and available time slots will appear. Select the time that suits you best',
        ],
        'video' => 'videos/steps/date&time.mp4',
    ],
];
