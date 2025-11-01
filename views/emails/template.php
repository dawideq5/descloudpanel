<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { width: 100%; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background-color: #f7f7f7; padding: 10px 20px; text-align: center; border-bottom: 1px solid #ddd; }
        .content { padding: 30px 20px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #777; }
        .button { display: inline-block; padding: 12px 25px; margin: 20px 0; background-color: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{APP_NAME}}</h2>
        </div>
        <div class="content">
            <h1>{{TITLE}}</h1>
            <p>{{MESSAGE}}</p>
            {{BUTTON}}
            <p>Jeśli nie inicjowałeś/aś tej akcji, zignoruj tę wiadomość.</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> {{APP_NAME}}. Wszelkie prawa zastrzeżone.</p>
        </div>
    </div>
</body>
</html>
