-- Tabel History_FRS: menyimpan snapshot setiap kali FRS di-edit
-- Setiap versi punya Versi (nomor urut) per NPM+Semester
CREATE TABLE History_FRS (
    Id_History  INT IDENTITY(1,1) PRIMARY KEY,
    NPM         CHAR(10) NOT NULL,
    Id_Sem      VARCHAR(10) NOT NULL,
    Id_MK       VARCHAR(10) NOT NULL,
    Versi       INT NOT NULL,
    Disimpan_pada DATETIME NOT NULL DEFAULT GETDATE(),
    FOREIGN KEY (NPM) REFERENCES Mahasiswa(NPM),
    FOREIGN KEY (Id_Sem) REFERENCES Semester(Id_Sem),
    FOREIGN KEY (Id_MK) REFERENCES MataKuliah(Id_MK)
);
