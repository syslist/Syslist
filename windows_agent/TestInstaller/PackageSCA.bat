echo Starting Packaging
echo "%NSISLOC%\makensisw.exe" TestInstall.nsi
"%NSISLOC%\makensis.exe" TestInstall.nsi
"%NSISLOC%\makensis.exe" SCAACCInstaller.nsi
"%NSISLOC%\makensis.exe" DemoInstall.nsi
"%NSISLOC%\makensis.exe" ASPDemoInstall.nsi
"%NSISLOC%\makensis.exe" SCADistInstaller.nsi
"%NSISLOC%\makensis.exe" SCADistDemoInstaller.nsi
"%NSISLOC%\makensis.exe" SCADistASPDemoInstaller.nsi
"%NSISLOC%\makensis.exe" SCADistACC.nsi
echo Done with Packaging
