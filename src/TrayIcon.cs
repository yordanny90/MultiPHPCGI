using System;
using System.IO;
using System.Threading;
using System.Diagnostics;
using System.Windows.Forms;
using System.Drawing;
using System.Security.Cryptography;
using System.Text;

public class TrayIcon
{
    private NotifyIcon notifyIcon;
    private ContextMenuStrip contextMenu;
    private string dir;

    public TrayIcon(string dir)
    {
        this.dir = dir;
        this.notifyIcon = new NotifyIcon();
        this.notifyIcon.Icon = new Icon(Path.Combine(this.dir, "app", "favicon.ico")); // Ruta correcta al icono
        this.notifyIcon.Visible = true;
        this.notifyIcon.Text = "MultiPHPCGI ("+dir+")";
        this.contextMenu = new ContextMenuStrip();

        AddMenuCmd(dir, "explorer", "\""+dir+"\"");
        this.contextMenu.Items.Add(new ToolStripSeparator());
        AddMenuCmd("Abrir App", Path.Combine(this.dir, "app-open.bat"), "");
        AddMenuCmd("Iniciar App", Path.Combine(this.dir, "app-start.bat"), "");
        AddMenuCmd("Detener App", Path.Combine(this.dir, "app-stop.bat"), "");
        this.contextMenu.Items.Add(new ToolStripSeparator());
        AddMenuCmd("Iniciar NGINX", Path.Combine(this.dir, "service-start.bat"), "");
        AddMenuCmd("Detener NGINX", Path.Combine(this.dir, "service-stop.bat"), "");
        this.contextMenu.Items.Add(new ToolStripSeparator());
        AddMenuCmd("Detener Todo", Path.Combine(this.dir, "stop-all.bat"), "");
        this.contextMenu.Items.Add(new ToolStripSeparator());
        ToolStripMenuItem salir = new ToolStripMenuItem
        {
            Text = "Salir"
        };
        salir.Click += (sender, e) => ConfirmExit();
        this.contextMenu.Items.Add(salir);
        this.notifyIcon.ContextMenuStrip = this.contextMenu;
    }

    private void AddMenuCmd(string text, string command, string args)
    {
        if (command != null)
        {
            ToolStripMenuItem menuItem = new ToolStripMenuItem
            {
                Text = text
            };
            menuItem.Click += (sender, e) => ExecuteCmd(command, args);
            this.contextMenu.Items.Add(menuItem);
        }

    }

    private void ConfirmExit()
    {
        // Mostrar un cuadro de confirmación
        DialogResult result = MessageBox.Show(
            "Esto no detiene los servicios iniciados\n¿Estás seguro que deseas salir?",
            "Confirmación de salida",
            MessageBoxButtons.YesNo,
            MessageBoxIcon.Question);

        if (result == DialogResult.Yes)
        {
            Application.Exit();
        }
    }

    private void ExecuteCmd(string command, string args)
    {
        ProcessStartInfo startInfo = new ProcessStartInfo
        {
            FileName = command,
            Arguments = args,  // El comando a ejecutar
            WindowStyle = ProcessWindowStyle.Normal // Normal|Hidden
        };
        Process.Start(startInfo);
    }

    public void Run()
    {
        Application.Run();
    }
}

public class Program
{
    public static string GetHash(string input)
    {
        using (MD5 hash = MD5.Create())
        {
            byte[] inputBytes = Encoding.UTF8.GetBytes(input);  // Convertir la cadena a bytes
            byte[] hashBytes = hash.ComputeHash(inputBytes);  // Calcular el hash

            // Convertir el hash en un formato legible (hexadecimal)
            StringBuilder sb = new StringBuilder();
            foreach (byte b in hashBytes)
            {
                sb.Append(b.ToString("x2"));  // Convertir cada byte a hexadecimal
            }
            return sb.ToString();
        }
    }

    public static void Main()
    {
        string dir = AppDomain.CurrentDomain.BaseDirectory;
        string hash = GetHash(dir);
        bool createdNew;
        using (Mutex mutex = new Mutex(true, "MPHPCGI:" + hash, out createdNew))
        {
            if (createdNew)
            {
                TrayIcon trayIcon = new TrayIcon(dir);
                trayIcon.Run();
            }
            else
            {
                MessageBox.Show("La aplicación ya está en ejecución.");
            }
        }
    }
}