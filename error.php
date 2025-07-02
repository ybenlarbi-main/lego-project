<?php
/**
 * Custom error handling page for Menalego
 * 
 * This page displays errors in a user-friendly format instead of showing raw PHP warnings
 */

// Get error details
$error_type = isset($_GET['type']) ? $_GET['type'] : 'Unknown';
$error_message = isset($_GET['message']) ? $_GET['message'] : 'An unknown error occurred.';
$error_file = isset($_GET['file']) ? $_GET['file'] : '';
$error_line = isset($_GET['line']) ? (int)$_GET['line'] : 0;
$back_url = isset($_GET['back']) ? $_GET['back'] : 'index.php';

// Determine if we're in the admin area
$is_admin = strpos($error_file, 'admin') !== false;
$admin_path = $is_admin ? '' : 'admin/';
$site_path = $is_admin ? '../' : '';

// Default title
$page_title = 'Error';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Menalego</title>
    <link rel="stylesheet" href="<?php echo $site_path; ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
        }
        
        .error-container {
            max-width: 900px;
            margin: 3rem auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .error-header {
            background: #ef4444;
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .error-icon {
            font-size: 2rem;
        }
        
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        .error-content {
            padding: 2rem;
        }
        
        .error-message {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 1.2rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
        }
        
        .error-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .error-detail-label {
            font-weight: 600;
            color: #374151;
        }
        
        .error-detail-value {
            color: #4b5563;
        }
        
        .error-action {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #0061FF;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #0052d6;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #4b5563;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
        }
        
        .error-debug {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .error-debug h3 {
            color: #374151;
            margin-top: 0;
        }
        
        .error-code {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 1.2rem;
            font-family: monospace;
            white-space: pre-wrap;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        
        .stack-trace {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="error-title">Une erreur est survenue</h1>
        </div>
        
        <div class="error-content">
            <div class="error-message">
                <?php echo htmlspecialchars($error_type . ': ' . $error_message); ?>
            </div>
            
            <div class="error-details">
                <div class="error-detail-label">Fichier:</div>
                <div class="error-detail-value"><?php echo htmlspecialchars($error_file); ?></div>
                
                <div class="error-detail-label">Ligne:</div>
                <div class="error-detail-value"><?php echo $error_line; ?></div>
            </div>
            
            <?php if ($error_file && $error_line && file_exists($error_file)): ?>
                <div class="error-debug">
                    <h3>Code source</h3>
                    <div class="error-code">
                        <?php
                        if (file_exists($error_file)) {
                            $lines = file($error_file);
                            $start = max(0, $error_line - 5);
                            $end = min(count($lines), $error_line + 5);
                            
                            echo '<div class="stack-trace">';
                            for ($i = $start; $i < $end; $i++) {
                                $lineNum = $i + 1;
                                $highlight = $lineNum == $error_line ? 'style="background-color: #fecaca; font-weight: bold;"' : '';
                                echo "<div $highlight><span style=\"color: #9ca3af; margin-right: 1rem;\">$lineNum</span>" . htmlspecialchars($lines[$i]) . "</div>";
                            }
                            echo '</div>';
                        } else {
                            echo "Le fichier n'existe pas.";
                        }
                        ?>
                    </div>
                    
                    <div style="margin-top: 1rem; font-size: 0.9rem; color: #6b7280;">
                        <strong>Note:</strong> Cette information n'est visible que pour les administrateurs.
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="error-action">
                <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <a href="<?php echo $site_path; ?>index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <?php if ($is_admin): ?>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-store"></i> Site principal
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
