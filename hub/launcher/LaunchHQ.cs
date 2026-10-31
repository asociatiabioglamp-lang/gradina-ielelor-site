using System;
using System.Diagnostics;
using System.IO;
using System.Threading;

class LaunchHQ
{
    static Process server;
    static Process vite;

    static void Main()
    {
        string dir = Path.GetDirectoryName(System.Reflection.Assembly.GetExecutingAssembly().Location);
        string node = Path.Combine(dir, @".tools\node\node.exe");
        string viteJs = Path.Combine(dir, @"node_modules\vite\bin\vite.js");

        if (!File.Exists(node)) { Fail("Nu gasesc Node.js la: " + node); return; }
        if (!File.Exists(viteJs)) { Fail("Nu gasesc aplicatia (node_modules). Reinstalati dependentele."); return; }

        Console.WriteLine("==================================================");
        Console.WriteLine("  GRADINA IELELOR HQ - aplicatie de management");
        Console.WriteLine("==================================================");
        Console.WriteLine("");
        Console.WriteLine("Pornesc serverul de date (port 8787)...");
        server = StartProc(node, "server\\index.js", dir);
        Thread.Sleep(3500);

        Console.WriteLine("Pornesc interfata aplicatiei (port 7100)...");
        vite = StartProc(node, "\"" + viteJs + "\"", dir);

        Thread browser = new Thread(OpenBrowser);
        browser.IsBackground = true;
        browser.Start();

        Console.WriteLine("");
        Console.WriteLine("Aplicatia ruleaza. Se deschide in browser automat.");
        Console.WriteLine("Puteti minimiza aceasta fereastra, dar NU o inchideti");
        Console.WriteLine("cat timp folositi aplicatia. Ctrl+C o opreste complet.");
        Console.WriteLine("");

        Console.CancelKeyPress += delegate(object s, ConsoleCancelEventArgs e)
        {
            e.Cancel = true;
            KillAll();
            Environment.Exit(0);
        };

        // daca una din componente se opreste, oprim tot
        Thread watcher = new Thread(delegate()
        {
            try { vite.WaitForExit(); } catch { }
            try { if (server != null && !server.HasExited) server.Kill(); } catch { }
        });
        watcher.IsBackground = true;
        watcher.Start();

        try { server.WaitForExit(); } catch { }
        KillAll();
    }

    static Process StartProc(string exe, string args, string dir)
    {
        ProcessStartInfo psi = new ProcessStartInfo();
        psi.FileName = exe;
        psi.Arguments = args;
        psi.WorkingDirectory = dir;
        psi.UseShellExecute = false;
        psi.CreateNoWindow = true;
        psi.RedirectStandardOutput = true;
        psi.RedirectStandardError = true;
        Process p = new Process();
        p.StartInfo = psi;
        p.OutputDataReceived += delegate(object s, DataReceivedEventArgs e) { if (e.Data != null) Console.WriteLine("  | " + e.Data); };
        p.ErrorDataReceived += delegate(object s, DataReceivedEventArgs e) { if (e.Data != null) Console.WriteLine("  ! " + e.Data); };
        p.Start();
        p.BeginOutputReadLine();
        p.BeginErrorReadLine();
        return p;
    }

    static void OpenBrowser()
    {
        Thread.Sleep(9000);
        try { Process.Start("http://localhost:7100"); } catch { }
    }

    static void KillAll()
    {
        try { if (server != null && !server.HasExited) server.Kill(); } catch { }
        try { if (vite != null && !vite.HasExited) vite.Kill(); } catch { }
    }

    static void Fail(string msg)
    {
        Console.WriteLine("EROARE: " + msg);
        Console.WriteLine("Apasati o tasta pentru a iesi.");
        try { Console.ReadKey(); } catch { }
    }
}
