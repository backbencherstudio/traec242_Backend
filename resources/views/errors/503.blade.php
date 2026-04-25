<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Maintenance</title>
    <style>
        :root {
            color-scheme: light;
            --canvas: #f6efe6;
            --canvas-accent: #f3d9b1;
            --ink: #1f2937;
            --muted: #5f6b7a;
            --card: rgba(255, 252, 247, 0.86);
            --card-border: rgba(143, 114, 82, 0.2);
            --primary: #b45309;
            --primary-soft: rgba(180, 83, 9, 0.12);
            --shadow: 0 24px 70px rgba(89, 54, 16, 0.16);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.9), transparent 34%),
                radial-gradient(circle at bottom right, rgba(233, 180, 76, 0.24), transparent 28%),
                linear-gradient(145deg, var(--canvas) 0%, #f7f1ea 45%, #efe3d1 100%);
            color: var(--ink);
            display: grid;
            place-items: center;
            padding: 32px 20px;
        }

        body::before,
        body::after {
            content: "";
            position: fixed;
            border-radius: 999px;
            z-index: 0;
            filter: blur(8px);
        }

        body::before {
            width: 260px;
            height: 260px;
            top: 8%;
            right: 10%;
            background: rgba(180, 83, 9, 0.12);
        }

        body::after {
            width: 320px;
            height: 320px;
            bottom: 4%;
            left: 6%;
            background: rgba(75, 85, 99, 0.08);
        }

        .shell {
            position: relative;
            z-index: 1;
            width: min(100%, 1040px);
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
            overflow: hidden;
        }

        .content {
            padding: 56px 40px;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.52), rgba(255, 255, 255, 0)),
                linear-gradient(140deg, rgba(255, 255, 255, 0.1), rgba(180, 83, 9, 0.04));
            text-align: center;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: var(--primary-soft);
            color: var(--primary);
            font-size: 0.88rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: currentColor;
            box-shadow: 0 0 0 6px rgba(180, 83, 9, 0.12);
        }

        h1 {
            margin: 24px auto 16px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.6rem, 6vw, 4.7rem);
            line-height: 0.98;
            letter-spacing: -0.04em;
        }

        .lead {
            margin: 0 auto;
            color: var(--muted);
            font-size: 1.06rem;
            line-height: 1.9;
        }

        .actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 14px;
            margin-top: 28px;
        }

        .button,
        .ghost {
            text-decoration: none;
            border-radius: 999px;
            padding: 14px 20px;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .button {
            background: var(--ink);
            color: #fff8ef;
            box-shadow: 0 16px 24px rgba(31, 41, 55, 0.16);
        }

        .ghost {
            color: var(--ink);
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(31, 41, 55, 0.1);
        }

        .button:hover,
        .ghost:hover {
            transform: translateY(-1px);
        }

        @media (max-width: 640px) {
            .content {
                padding: 34px 22px;
            }

            h1 {
                max-width: none;
            }
        }
    </style>
</head>

<body>
    <main class="shell">
        <section class="panel">
            <div class="content">
                <span class="eyebrow">Maintenance Window Active</span>
                <h1>We'll be back before long.</h1>
                <p class="lead">
                    We are applying a quick update right now.
                    Service will be available again very soon. <br>
                    Thank you for your patience.
                </p>
            </div>
        </section>
    </main>
</body>

</html>
