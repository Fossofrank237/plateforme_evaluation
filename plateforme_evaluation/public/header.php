<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QuizSystem</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #ffffff 0%, #f5f7fa 100%);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                rgba(255,255,255,0.1) 0%,
                rgba(255,255,255,0.2) 50%,
                rgba(255,255,255,0) 100%
            );
            transform: rotate(45deg);
            transition: all 0.3s ease;
            z-index: 1;
        }
        
        .stat-card:hover::before {
            transform: rotate(45deg) translate(50%, 50%);
        }
        
        .progress-ring {
            transition: stroke-dashoffset 0.3s ease;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse-effect {
            animation: pulse 2s infinite;
        }
    </style>
</head>
    <body class="bg-gray-100 text-gray-800">
