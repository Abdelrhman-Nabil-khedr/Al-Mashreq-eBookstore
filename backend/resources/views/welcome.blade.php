<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دار المشرق للنشر والتوزيع - API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #090d16 0%, #111827 50%, #1f2937 100%);
            color: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .card {
            max-width: 640px;
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .badge {
            display: inline-block;
            background: rgba(217, 119, 6, 0.2);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            background: linear-gradient(to right, #ffffff, #d1d5db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            color: #9ca3af;
            line-height: 1.7;
            margin-bottom: 2rem;
        }
        .links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background: #d97706;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #b45309;
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            color: #e5e7eb;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.14);
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Laravel 11 • RESTful API & Filament Admin</span>
        <h1>مكتبة المشرق الإلكترونية</h1>
        <p>نظام الواجهة الخلفية وإدارة المحتوى والكتالوج الرقمي لدار المشرق للنشر والتوزيع.</p>
        <div class="links">
            <a href="/admin" class="btn btn-primary">لوحة التحكم (Filament)</a>
            <a href="/api/health" class="btn btn-secondary">فحص الحالة (API Health)</a>
        </div>
    </div>
</body>
</html>
