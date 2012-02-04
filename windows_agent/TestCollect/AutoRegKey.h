#ifndef AUTO_REG_KEY_H_INcLUDED
#define AUTO_REG_KEY_H_INcLUDED

class AutoRegKey
{
public:
	AutoRegKey(): m_RegKey(0) {};

	~AutoRegKey() 
	{
		Close();
	};

	HKEY * operator& () 
	{
		assert(m_RegKey == 0);
		return &m_RegKey;

	};

	operator= (HKEY NewKey)
	{
		Close();

		m_RegKey = NewKey;
	};

	void Close()
	{ 
		if (m_RegKey != 0) {
			RegCloseKey(m_RegKey); 
			m_RegKey = 0;
		}
	};

	operator HKEY () 
	{
		return m_RegKey;
	};

private:

	HKEY m_RegKey;
};
#endif