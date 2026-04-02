<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoLib - One API for Web Scraping, PDF Generation & Email Automation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
            font-weight: 500;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #ff6b6b;
            color: white;
        }

        .btn-secondary:hover {
            background: #ee5a52;
            transform: translateY(-2px);
        }

        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: slideDown 0.8s ease;
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: slideUp 0.8s ease;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeIn 1s ease;
        }

        .features {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .features h2 {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.5rem;
            color: #333;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: #f8f8f8;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            margin-bottom: 1rem;
            color: #667eea;
        }

        .feature-card p {
            color: #666;
        }

        .quick-start {
            background: #f8f9fa;
            padding: 4rem 2rem;
            text-align: center;
        }

        .quick-start h2 {
            margin-bottom: 2rem;
            font-size: 2.5rem;
            color: #333;
        }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 2rem;
            border-radius: 8px;
            text-align: left;
            max-width: 600px;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }

        .pricing {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .pricing h2 {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.5rem;
            color: #333;
        }

        .pricing-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            max-width: 300px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .pricing-card h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        .price {
            font-size: 3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }

        .price span {
            font-size: 1rem;
            color: #666;
        }

        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <header>
        <div class="navbar">
            <div class="logo">🔧 AutoLib</div>
            <nav class="nav-links">
                <a href="#features">Features</a>
                <a href="#quick-start">Quick Start</a>
                <a href="#pricing">Pricing</a>
                <a href="./API.md" class="btn btn-warning">API Docs</a>
                <a href="{{ route('login') }}" class="btn btn-secondary">Sign In</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>One API for Web Scraping, PDF Generation & Email Automation</h1>
            <p>Stop juggling 3 separate services. AutoLib gives you all three behind a single REST API, starting at $10/month.</p>
            <div class="hero-buttons">
                <a href="{{ route('register') }}" class="btn-primary">Get Started</a>
                <a href="./API.md" class="btn-secondary">View Docs</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2>What is AutoLib?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🌐</div>
                <h3>Web Scraping</h3>
                <p>Scrape any page with CSS selectors. Static or JS-rendered content. Perfect for data extraction and monitoring.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📄</div>
                <h3>PDF Generation</h3>
                <p>Convert HTML or any URL to a pixel-perfect PDF. Generate invoices, reports, and documents on-demand.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📧</div>
                <h3>Email Automation</h3>
                <p>Send transactional emails reliably via AWS SES. Templates, attachments, and delivery tracking included.</p>
            </div>
        </div>
    </section>

    <!-- Quick Start Section -->
    <section class="quick-start" id="quick-start">
        <h2>Quick Start</h2>
        <p>Get your API key at autolib.dev and make your first request:</p>
        <div class="code-block">
curl -X POST /api/scrape \<br>
  -H "Authorization: Bearer al_YOUR_KEY" \<br>
  -H "Content-Type: application/json" \<br>
  -d '{"url": "https://example.com", "selector": "h1"}'<br><br>
Response:<br>
{<br>
  "url": "https://example.com",<br>
  "selector": "h1",<br>
  "results": ["Example Domain"],<br>
  "count": 1,<br>
  "method": "static"<br>
}
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <h2>Pricing</h2>
        <div class="pricing-card">
            <h3>Developer Plan</h3>
            <div class="price">$10<span>/month</span></div>
            <p>Perfect for solo developers and small projects. Includes 1,000 API calls per month.</p>
            <a href="{{ route('register') }}" class="btn btn-primary" style="margin-top: 1rem;">Start Free Trial</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 AutoLib - Built for developers, by developers.</p>
        <p>Questions? Contact us at support@autolib.dev</p>
    </footer>
</body>
</html>