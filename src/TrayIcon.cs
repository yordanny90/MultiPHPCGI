using System;
using System.IO;
using System.Threading;
using System.Diagnostics;
using System.Drawing;
using System.Windows.Forms;
using System.Security.Cryptography;
using System.Runtime.InteropServices;
using System.Text;
using System.Reflection;

public class TrayIcon
{
    private NotifyIcon notifyIcon;
    private ContextMenuStrip contextMenu;
    private ToolStripMenuItem serverMenu;
    private string dir;

    public TrayIcon(string dir)
    {
        this.dir = dir;
        this.notifyIcon = new NotifyIcon();
        this.notifyIcon.Icon = new Icon(Path.Combine(this.dir, "bin", "app", "favicon.ico")); // Ruta correcta al icono
        this.notifyIcon.Visible = true;
        this.notifyIcon.Text = "MultiPHPCGI (" + dir + ")";
        this.contextMenu = new ContextMenuStrip();
        this.serverMenu = new ToolStripMenuItem("Servidores", GetShellIcon(35));
        this.serverMenu.DropDownOpening += FillServers;
        this.serverMenu.DropDownItems.Add(new ToolStripSeparator());

        ToolStripMenuItem opciones = new ToolStripMenuItem(" [MultiPHPCGI] ");
        this.contextMenu.Items.Add(opciones);
        AddMenuCmd(dir, "explorer", "\"" + dir + "\"", GetShellIcon(3));
        AddMenuCmd("CMD", Path.Combine(this.dir, "mcli.bat"), "", GetShellIcon(71));
        this.contextMenu.Items.Add(new ToolStripSeparator());
        AddMenuCmd("config.ini", Path.Combine(this.dir, "inc", "config.ini"), "", GetShellIcon(269));
        this.contextMenu.Items.Add(this.serverMenu);
        this.contextMenu.Items.Add(new ToolStripSeparator());
        AddMenuCmd("Iniciar Servicios", Path.Combine(this.dir, "service-start.bat"), "", GetShellIcon(137));
        AddMenuCmd("Detener Servicios", Path.Combine(this.dir, "service-stop.bat"), "", GetShellIcon(219));
        ToolStripMenuItem menuItem = new ToolStripMenuItem("Variables del sistema", GetShellIcon(314));
        menuItem.Click += (sender, e) => ExecuteCmd("SystemPropertiesAdvanced.exe", "");
        opciones.DropDownItems.Add(menuItem);
        menuItem = new ToolStripMenuItem("Monitor de recursos", GetShellIcon(314));
        menuItem.Click += (sender, e) => ExecuteCmd("resmon.exe", "");
        opciones.DropDownItems.Add(menuItem);
        opciones.DropDownItems.Add(new ToolStripSeparator());
        menuItem = new ToolStripMenuItem("Detener/Salir", GetShellIcon(27));
        menuItem.Click += (sender, e) =>
        {
            ExecuteCmd(Path.Combine(this.dir, "service-stop.bat"), "");
            ConfirmExit();
        };
        opciones.DropDownItems.Add(menuItem);
        menuItem = new ToolStripMenuItem("Salir", GetShellIcon(131));
        menuItem.Click += (sender, e) => ConfirmExit();
        opciones.DropDownItems.Add(menuItem);
        int indice = 250;
        opciones.MouseDown += (sender, e) =>
        {
            indice=indice-(e.Button == MouseButtons.Right ? 1 : -1);
            opciones.Image = GetShellIcon(indice);
            opciones.Text = "Icono: " + indice;
        };
        this.notifyIcon.ContextMenuStrip = this.contextMenu;
    }

    // Iconos de carpetas desde Shell32.dll
    private Image GetShellIcon(int iconIndex = 3) // 3 = carpeta amarilla clásica
    {
        IntPtr hIcon = ExtractIcon(IntPtr.Zero, "shell32.dll", iconIndex);
        if (hIcon != IntPtr.Zero)
        {
            Icon icon = Icon.FromHandle(hIcon);
            Image img = new Bitmap(icon.ToBitmap(), 16, 16);
            DestroyIcon(hIcon);
            return img;
        }
        return null;
    }
    
    [DllImport("user32.dll")]
    private static extern bool DestroyIcon(IntPtr handle);

    [DllImport("shell32.dll")]
    private static extern IntPtr ExtractIcon(IntPtr hInst, string lpszExeFileName, int nIconIndex);

    private void FillServers(object sender, EventArgs e)
    {
        ToolStripMenuItem submenu=this.serverMenu;
        submenu.DropDownItems.Clear();
        string carpetaConf=Path.Combine(this.dir, "conf", "nginx", "conf", "sites-enabled");

        ToolStripMenuItem btn = new ToolStripMenuItem("Explorar", GetShellIcon(3));
        btn.Click += (s, args) => ExecuteCmd("explorer", carpetaConf);
        submenu.DropDownItems.Add(btn);
        submenu.DropDownItems.Add(new ToolStripSeparator());
        if (Directory.Exists(carpetaConf))
        {
            // LEER todos los .conf ORDENADOS alfabéticamente
            string[] archivosConf = Directory.GetFiles(carpetaConf, "*.conf", SearchOption.TopDirectoryOnly);

            if (archivosConf.Length > 0)
            {
                foreach (string archivoCompleto in archivosConf)
                {
                    string nombreConf = Path.GetFileNameWithoutExtension(archivoCompleto);
                    btn = new ToolStripMenuItem(nombreConf + ".conf ", GetShellIcon(269));
                    btn.Click += (s, args) => ExecuteCmd(archivoCompleto, "");
                    submenu.DropDownItems.Add(btn);
                }
            }
        }
        submenu.DropDownItems.Add(new ToolStripSeparator());
        btn = new ToolStripMenuItem("Agregar Servidor", GetShellIcon(296));
        btn.Click += (s, args) => ExecuteCmd(Path.Combine(this.dir, "bin", "add-server.bat"), "");
        submenu.DropDownItems.Add(btn);
        btn = new ToolStripMenuItem("Regenerar .conf", GetShellIcon(238));
        btn.Click += (s, args) => ExecuteCmd(Path.Combine(this.dir, "bin", "init-servers.bat"), "");
        submenu.DropDownItems.Add(btn);
    }

    private void AddMenuCmd(string text, string command, string args, Image img=null)
    {
        if (command != null)
        {
            ToolStripMenuItem menuItem = new ToolStripMenuItem(text, img);
            menuItem.Click += (sender, e) => ExecuteCmd(command, args);
            this.contextMenu.Items.Add(menuItem);
        }
    }

    private void ConfirmExit()
    {
        Application.Exit();
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